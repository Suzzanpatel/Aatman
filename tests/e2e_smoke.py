"""
End-to-end smoke + security tests for Aatman.

Requirements: Python 3.9+, `pip install requests`, a running app and a MySQL/MariaDB
database freshly loaded with database/schema.sql + database/seed.sql.

    php -S 127.0.0.1:8080 -t public            # terminal 1
    python3 tests/e2e_smoke.py                 # terminal 2

Environment variables:
    AATMAN_BASE_URL   default http://127.0.0.1:8080/
    AATMAN_MYSQL      command used to query the DB, default "mysql -u root aatman"

The tests modify data, so reload schema.sql + seed.sql before each run.
"""
import os, re, shlex, sys, subprocess
import requests

B = os.environ.get("AATMAN_BASE_URL", "http://127.0.0.1:8080/").rstrip("/") + "/"
MYSQL = shlex.split(os.environ.get("AATMAN_MYSQL", "mysql -u root aatman"))
fails = []
def check(cond, msg):
    print(("PASS " if cond else "FAIL ") + msg)
    if not cond: fails.append(msg)

def tok(html):
    m = re.search(r'name="csrf_token" value="([0-9a-f]+)"', html) or re.search(r'name="csrf-token" content="([0-9a-f]+)"', html)
    return m.group(1) if m else None

def sql(q):
    return subprocess.run(MYSQL + ["-N", "-e", q], capture_output=True, text=True).stdout.strip()

def login(email, pw="Aatman@123"):
    s = requests.Session()
    t = tok(s.get(B+"login.php").text)
    r = s.post(B+"login.php", data={"email":email,"password":pw,"csrf_token":t})
    return s, r

# --- pages render
for p in ["", "index.php", "login.php", "signup.php"]:
    r = requests.get(B+p); check(r.status_code==200 and "Warning" not in r.text and "Fatal" not in r.text, f"GET /{p} renders")

# --- every role logs in and lands on its dashboard without PHP warnings
for email, path in [("admin@aatman.test","dashboard/admin.php"),("ngo@aatman.test","dashboard/ngo.php"),
                    ("donor@aatman.test","dashboard/donor.php"),("volunteer@aatman.test","dashboard/volunteer.php"),
                    ("needy@aatman.test","dashboard/needy.php")]:
    s, r = login(email)
    check(r.url.endswith(path) and r.status_code==200, f"{email} -> {path}")
    check("Warning" not in r.text and "Notice" not in r.text and "Fatal" not in r.text and "Deprecated" not in r.text, f"{path} has no PHP warnings")

# --- donor campaigns now show NGO names via correct join, and pending requests visible
s,_ = login("donor@aatman.test"); h = s.get(B+"dashboard/donor.php").text
check("Winter Blanket Drive" in h and "Asha Foundation" in h and "Back to School Kits" not in h, "donor sees active campaigns with correct NGO name")
check("Monthly rations" in h and "Notebooks for kids" in h, "donor sees pending requests")

# --- RBAC: non-admin cannot open admin; donor cannot open ngo/volunteer pages
for email in ["donor@aatman.test","ngo@aatman.test","needy@aatman.test","volunteer@aatman.test"]:
    s,_ = login(email)
    r = s.get(B+"dashboard/admin.php"); check(r.status_code==403, f"{email} blocked from admin (403)")
s,_ = login("donor@aatman.test")
for p in ["dashboard/volunteer.php","dashboard/ngo.php","dashboard/needy.php","dashboard/campaign_create.php"]:
    check(s.get(B+p).status_code==403, f"donor blocked from {p}")
r = requests.get(B+"dashboard/admin.php"); check(r.url.endswith("login.php"), "anonymous redirected from admin to login")

# --- signup cannot self-assign admin
s = requests.Session(); t = tok(s.get(B+"signup.php").text)
r = s.post(B+"signup.php", data={"name":"Evil","email":"evil@x.test","password":"Password1!","password_confirm":"Password1!","role":"admin","csrf_token":t})
check(sql("SELECT COUNT(*) FROM users WHERE email='evil@x.test'")=="0" and "valid role" in r.text, "signup with role=admin rejected")

# --- missing CSRF token rejected
s = requests.Session(); s.get(B+"signup.php")
r = s.post(B+"signup.php", data={"name":"X","email":"x@x.test","password":"Password1!","password_confirm":"Password1!","role":"donor"})
check(r.status_code==419 and sql("SELECT COUNT(*) FROM users WHERE email='x@x.test'")=="0", "POST without CSRF token rejected (419)")

# --- full signup + onboarding flow (volunteer), auto-login
s = requests.Session(); t = tok(s.get(B+"signup.php").text)
r = s.post(B+"signup.php", data={"name":"New Vol","email":"NewVol@x.test","password":"Password1!","password_confirm":"Password1!","role":"volunteer","phone":"9876543210","csrf_token":t})
check(r.url.endswith("onboarding/volunteer.php"), "signup redirects to volunteer onboarding")
t = tok(r.text)
r = s.post(B+"onboarding/volunteer.php", data={"csrf_token":t,"skills":"Cooking","availability":"Sundays","city":"Pune","preferred_category":"Health"})
check(r.url.endswith("dashboard/volunteer.php") and "Cooking" in r.text, "volunteer onboarding saves and logs in")
check(sql("SELECT email FROM users WHERE email='newvol@x.test'")=="newvol@x.test", "email stored lower-case")

# --- IDOR: onboarding page ignores ?user_id and requires session
r = requests.get(B+"onboarding/ngo.php?user_id=2"); check(r.url.endswith("login.php"), "anonymous onboarding?user_id=2 redirected to login")

# --- volunteer update profile with 'Other' free text and status
t = tok(s.get(B+"dashboard/volunteer.php").text)
r = s.post(B+"dashboard/volunteer.php", data={"csrf_token":t,"update_profile":"1","availability":"Evenings","preferred_category":"Other","preferred_category_other":"Animal welfare","status":"disabled","skills":"Cooking"})
check(sql("SELECT CONCAT(v.preferred_category,'|',v.status,'|',v.availability) FROM volunteer_details v JOIN users u ON u.id=v.id WHERE u.email='newvol@x.test'")=="Animal welfare|disabled|Evenings", "volunteer profile update incl. Other category + status")

# --- needy: create request (client-sent status ignored), complete, delete; IDOR on other's request
s,_ = login("needy@aatman.test"); t = tok(s.get(B+"dashboard/needy.php").text)
r = s.post(B+"dashboard/needy.php", data={"csrf_token":t,"create_request":"1","title":"Medicine help","description":"BP tablets","request_type":"Medicine","quantity":"3","unit":"strips","location":"Vadodara","priority":"High","contact_person_name":"Kamla","contact_person_phone":"9000000005","status":"completed"})
rid = sql("SELECT id FROM requests WHERE title='Medicine help'")
check(rid!="" and sql(f"SELECT status FROM requests WHERE id={rid}")=="pending", "needy creates request; status forced to pending")
r = s.post(B+"dashboard/needy.php", data={"csrf_token":t,"create_request":"1","title":"Bad","description":"x","request_type":"Weapons","quantity":"-5","unit":"x","location":"x","contact_person_name":"x","contact_person_phone":"abc"})
check(sql("SELECT COUNT(*) FROM requests WHERE title='Bad'")=="0", "invalid request input rejected")
ngo_req = sql("SELECT id FROM requests WHERE title='Notebooks for kids'")
s.post(B+"dashboard/request_action.php", data={"csrf_token":t,"request_id":ngo_req,"action":"delete"})
check(sql(f"SELECT COUNT(*) FROM requests WHERE id={ngo_req}")=="1", "needy cannot delete NGO's request (IDOR blocked)")
s.post(B+"dashboard/request_action.php", data={"csrf_token":t,"request_id":rid,"action":"complete"})
check(sql(f"SELECT status FROM requests WHERE id={rid}")=="completed", "needy marks own request completed")
s.post(B+"dashboard/request_action.php", data={"csrf_token":t,"request_id":rid,"action":"delete"})
check(sql(f"SELECT COUNT(*) FROM requests WHERE id={rid}")=="0", "needy deletes own request")
r = s.get(B+"dashboard/needy.php?delete_id=1"); check(sql("SELECT COUNT(*) FROM requests WHERE id=1")=="1", "old GET ?delete_id no longer deletes")

# --- needy profile update
t = tok(s.get(B+"dashboard/needy.php").text)
s.post(B+"dashboard/needy.php", data={"csrf_token":t,"update_profile":"1","user_name":"Kamla D","email":"needy@aatman.test","phone":"9000000005","contact_person_name":"Son","contact_person_phone":"9000000099","city":"Baroda"})
check(sql("SELECT CONCAT(u.name,'|',p.city,'|',p.contact_person_name) FROM users u JOIN people_in_need_details p ON p.id=u.id WHERE u.id=5")=="Kamla D|Baroda|Son", "needy profile update")
# email collision
s.post(B+"dashboard/needy.php", data={"csrf_token":t,"update_profile":"1","user_name":"Kamla D","email":"donor@aatman.test","phone":"","contact_person_name":"Son","contact_person_phone":"9000000099"})
check(sql("SELECT email FROM users WHERE id=5")=="needy@aatman.test", "profile email collision rejected")

# --- NGO: create campaign, change own campaign status, cannot change other NGO's; NGO profile update (table case fix)
s,_ = login("ngo@aatman.test"); t = tok(s.get(B+"dashboard/campaign_create.php").text)
s.post(B+"dashboard/campaign_create.php", data={"csrf_token":t,"title":"Food Drive","description":"Meals","category":"Health","contact_person_name":"Asha","contact_person_phone":"9000000002","start_date":"2026-12-01","end_date":"2026-12-10"})
cid = sql("SELECT id FROM campaigns WHERE title='Food Drive'")
check(cid!="" and sql(f"SELECT ngo_id FROM campaigns WHERE id={cid}")=="2", "NGO creates campaign owned by itself")
s.post(B+"dashboard/campaign_create.php", data={"csrf_token":t,"title":"Bad dates","description":"x","category":"Health","contact_person_name":"A","contact_person_phone":"9000000002","start_date":"2026-12-10","end_date":"2026-12-01"})
check(sql("SELECT COUNT(*) FROM campaigns WHERE title='Bad dates'")=="0", "campaign end-before-start rejected")
s.post(B+"dashboard/campaign_status.php", data={"csrf_token":t,"campaign_id":cid,"status":"completed"})
check(sql(f"SELECT status FROM campaigns WHERE id={cid}")=="completed", "NGO updates own campaign status (dropdown now works)")
other = sql("SELECT id FROM campaigns WHERE title='Plant 1000 Trees'")
s.post(B+"dashboard/campaign_status.php", data={"csrf_token":t,"campaign_id":other,"status":"disabled"})
check(sql(f"SELECT status FROM campaigns WHERE id={other}")=="active", "NGO cannot change another NGO's campaign")
t = tok(s.get(B+"dashboard/ngo.php").text)
s.post(B+"dashboard/ngo.php", data={"csrf_token":t,"update_profile":"1","user_name":"Asha F","email":"ngo@aatman.test","phone":"9000000002","organisation_name":"Asha Foundation Trust","address":"1 New Rd","category":"Education","city":"Vadodara"})
check(sql("SELECT organisation_name FROM ngo_details WHERE id=2")=="Asha Foundation Trust", "NGO profile update writes ngo_details")
s.post(B+"dashboard/ngo.php", data={"csrf_token":t,"create_request":"1","title":"Volunteers' food","description":"Snacks","request_type":"Food","quantity":"50","unit":"packets","location":"Vadodara","priority":"Low","contact_person_name":"Asha","contact_person_phone":"9000000002"})
check(sql("SELECT user_role FROM requests WHERE title=\"Volunteers' food\"")=="ngo", "NGO creates donor request")

# --- anonymous cannot change campaign status (was fully unauthenticated)
a = requests.Session(); ta = tok(a.get(B+"login.php").text)
a.post(B+"dashboard/campaign_status.php", data={"csrf_token":ta,"campaign_id":other,"status":"disabled"})
check(sql(f"SELECT status FROM campaigns WHERE id={other}")=="active", "anonymous cannot change campaign status")

# --- admin: change any campaign; deactivate user -> user's live session is killed and login blocked
adm,_ = login("admin@aatman.test"); t = tok(adm.get(B+"dashboard/admin.php").text)
adm.post(B+"dashboard/campaign_status.php", data={"csrf_token":t,"campaign_id":other,"status":"disabled"})
check(sql(f"SELECT status FROM campaigns WHERE id={other}")=="disabled", "admin can change any campaign status")
d,_ = login("donor@aatman.test")
adm.post(B+"dashboard/admin.php", data={"csrf_token":t,"set_user_status":"1","user_id":"3","status":"inactive","section":"donors"})
check(sql("SELECT status FROM users WHERE id=3")=="inactive", "admin deactivates donor")
r = d.get(B+"dashboard/donor.php"); check(r.url.endswith("login.php"), "deactivated donor's existing session is ended")
_, r = login("donor@aatman.test"); check("deactivated" in r.text and not r.url.endswith("donor.php"), "deactivated donor cannot log in")
adm.post(B+"dashboard/admin.php", data={"csrf_token":t,"set_user_status":"1","user_id":"1","status":"inactive"})
check(sql("SELECT status FROM users WHERE id=1")=="active", "admin cannot deactivate self")
adm.post(B+"dashboard/admin.php", data={"csrf_token":t,"set_user_status":"1","user_id":"3","status":"active"})

# --- donor self-disable
d,_ = login("donor@aatman.test"); t = tok(d.get(B+"dashboard/donor.php").text)
r = d.post(B+"dashboard/donor.php", data={"csrf_token":t,"disable_account":"1"})
check(sql("SELECT CONCAT(u.status,'|',d.status) FROM users u JOIN donor_details d ON d.id=u.id WHERE u.id=3")=="inactive|disabled" and r.url.endswith("login.php"), "donor self-deactivation")
sql("UPDATE users SET status='active' WHERE id=3"); sql("UPDATE donor_details SET status='active' WHERE id=3")

# --- donor profile update (state now editable)
d,_ = login("donor@aatman.test"); t = tok(d.get(B+"dashboard/donor.php").text)
d.post(B+"dashboard/donor.php", data={"csrf_token":t,"update_profile":"1","name":"Ravi M","email":"donor@aatman.test","phone":"9000000003","donation_type":"Services","preferred_category":"Women","address":"x","city":"Surat","state":"Gujarat"})
check(sql("SELECT CONCAT(donation_type,'|',preferred_category,'|',state) FROM donor_details WHERE id=3")=="Services|Women|Gujarat", "donor profile update")

# --- login: generic error, throttling, session id rotated
s = requests.Session(); s.get(B+"login.php"); before = s.cookies.get("AATMANSESSID")
_, r = login("nobody@aatman.test"); check("Invalid email or password" in r.text, "unknown email gives generic error")
_, r = login("ngo@aatman.test", "wrong"); check("Invalid email or password" in r.text, "wrong password gives same generic error")
for i in range(5): login("ngo2@aatman.test","wrong")
_, r = login("ngo2@aatman.test"); check("Too many failed login attempts" in r.text, "login throttled after 5 failures (even with right password)")
sql("DELETE FROM login_attempts")
t = tok(s.get(B+"login.php").text); s.post(B+"login.php", data={"email":"ngo@aatman.test","password":"Aatman@123","csrf_token":t})
check(s.cookies.get("AATMANSESSID") != before, "session id regenerated on login")

# --- logout requires POST+CSRF
s,_ = login("ngo@aatman.test"); s.get(B+"logout.php")
check(s.get(B+"dashboard/ngo.php").url.endswith("ngo.php"), "GET /logout.php does not log out (CSRF-safe)")
t = tok(s.get(B+"dashboard/ngo.php").text); s.post(B+"logout.php", data={"csrf_token":t})
check(s.get(B+"dashboard/ngo.php").url.endswith("login.php"), "POST logout ends session")

# --- XSS: stored payload is escaped
s,_ = login("needy@aatman.test"); t = tok(s.get(B+"dashboard/needy.php").text)
s.post(B+"dashboard/needy.php", data={"csrf_token":t,"create_request":"1","title":"<script>alert(1)</script>","description":"<img src=x onerror=alert(1)>","request_type":"Food","quantity":"1","unit":"kg","location":"x","priority":"Low","contact_person_name":"K","contact_person_phone":"9000000005"})
s2,_ = login("volunteer@aatman.test"); h = s2.get(B+"dashboard/volunteer.php").text
check("<script>alert(1)</script>" not in h and "&lt;script&gt;alert(1)&lt;/script&gt;" in h, "stored XSS payload is escaped")

# --- chatbot: CSRF, no CORS *, not-configured message, length cap
c = requests.Session(); h = c.get(B).text; t = tok(h)
r = c.post(B+"api/chatbot.php", json={"message":"hi"}); check(r.status_code==419, "chatbot rejects missing CSRF")
r = c.post(B+"api/chatbot.php", json={"message":"hi"}, headers={"X-CSRF-Token":t})
check(r.headers.get("Access-Control-Allow-Origin") is None, "chatbot sends no wildcard CORS header")
check(r.status_code==503 and "not configured" in r.json()["reply"], "chatbot w/o key returns friendly 503")
r = c.post(B+"api/chatbot.php", json={"message":"x"*600}, headers={"X-CSRF-Token":t}); check(r.status_code==422, "chatbot rejects >500 chars")
r = c.get(B+"api/chatbot.php"); check(r.status_code==405, "chatbot GET -> 405")

# --- security headers
r = requests.get(B+"login.php")
check(r.headers.get("X-Frame-Options")=="SAMEORIGIN" and r.headers.get("X-Content-Type-Options")=="nosniff", "security headers present")
check("HttpOnly" in r.headers.get("Set-Cookie","") and "SameSite=Lax" in r.headers.get("Set-Cookie",""), "session cookie HttpOnly + SameSite")

# --- dashboard/index router
s,_ = login("volunteer@aatman.test"); check(s.get(B+"dashboard/").url.endswith("dashboard/volunteer.php"), "dashboard/ router sends user to own dashboard")

print(f"\n{len(fails)} failure(s)")
sys.exit(1 if fails else 0)
