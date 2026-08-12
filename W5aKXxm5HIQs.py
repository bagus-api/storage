import sys, os, ctypes
try:
    if sys.gettrace() is not None:
        print("Debugger detected!"); sys.exit(1)
    if os.name == 'nt':
        if ctypes.windll.kernel32.IsDebuggerPresent():
            print("Debugger detected!"); sys.exit(1)
except: pass

import hmac, hashlib, requests, string, random, json, codecs, time, base64, signal, threading, re, subprocess, importlib, socket
from datetime import datetime
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad
import urllib3
urllib3.disable_warnings()

socket.setdefaulttimeout(5)
session = requests.Session()
adapter = requests.adapters.HTTPAdapter(pool_connections=200, pool_maxsize=200, max_retries=0)
session.mount('https://', adapter)
session.mount('http://', adapter)

_H1 = "VkxSVlZVRkZWVVZBVkZWQQ=="
_H2 = "U0dWeVZFRkZWRVZGVlVWQ0E9PQ=="
_H3 = "VkZSU1RVRkZWRVZGVlVWQ0E9PQ=="
_XOR = [0x42, 0x59, 0x53, 0x54, 0x41, 0x52, 0x47, 0x4d, 0x52]

def _get_hidden():
    try:
        s1 = base64.b64decode(_H3).decode()
        s2 = s1[::-1]
        s3 = base64.b64decode(s2).decode()
        return ''.join(chr(ord(s3[i]) ^ _XOR[i % len(_XOR)]) for i in range(len(s3)))
    except:
        return base64.b64decode("S0lOR1BBSU5aWQ==").decode()

_HIDDEN = _get_hidden()

RANK_CONFIG = [
    {"min_score": 5, "name": "Rare", "folder": "RARE ACCOUNTS", "prefix": "rare", "icon": "[RARE]"},
    {"min_score": 10, "name": "Epic", "folder": "EPIC ACCOUNTS", "prefix": "epic", "icon": "[EPIC]"},
    {"min_score": 20, "name": "Legendary", "folder": "LEGENDARY ACCOUNTS", "prefix": "legendary", "icon": "[LEGENDARY]"},
    {"min_score": 25, "name": "Mythic", "folder": "MYTHIC ACCOUNTS", "prefix": "mythic", "icon": "[MYTHIC]"}
]

def get_rank_by_score(score):
    sorted_ranks = sorted(RANK_CONFIG, key=lambda x: x["min_score"], reverse=True)
    for rank in sorted_ranks:
        if score >= rank["min_score"]:
            return rank
    return None

THRESHOLD = min(rank["min_score"] for rank in RANK_CONFIG) if RANK_CONFIG else 4

EXIT = False
OK = 0
TGT = 0
RARE_CNT = 0
CPL_CNT = 0
LOCK = threading.Lock()
print_lock = threading.Lock()

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
BASE_FOLDER = os.path.join(BASE_DIR, "NanasScript")
TOKENS_FOLDER = os.path.join(BASE_FOLDER, "TOKENS-JWT")
ACCOUNTS_FOLDER = os.path.join(BASE_FOLDER, "ACCOUNTS")
COUPLES_FOLDER = os.path.join(BASE_FOLDER, "COUPLES ACCOUNTS")
GHOST_FOLDER = os.path.join(BASE_FOLDER, "GHOST")
GHOST_ACCOUNTS = os.path.join(GHOST_FOLDER, "ACCOUNTS")
GHOST_COUPLES = os.path.join(GHOST_FOLDER, "COUPLESACCOUNT")

for folder in [BASE_FOLDER, TOKENS_FOLDER, ACCOUNTS_FOLDER, COUPLES_FOLDER, GHOST_FOLDER, GHOST_ACCOUNTS, GHOST_COUPLES]:
    os.makedirs(folder, exist_ok=True)

for rank in RANK_CONFIG:
    os.makedirs(os.path.join(BASE_FOLDER, rank["folder"]), exist_ok=True)
    os.makedirs(os.path.join(GHOST_FOLDER, rank["folder"]), exist_ok=True)

REGION_LANG = {"ME":"ar","IND":"hi","ID":"id","VN":"vi","TH":"th","BD":"bn","PK":"ur","TW":"zh","CIS":"ru","SAC":"es","BR":"pt"}
HEX_KEY = bytes.fromhex("32656534343831396539623435393838343531343130363762323831363231383734643064356437616639643866376530306331653534373135623764316533")

FILE_LOCKS = {}
def get_lock(fname):
    if fname not in FILE_LOCKS:
        FILE_LOCKS[fname] = threading.Lock()
    return FILE_LOCKS[fname]

PATTERNS = {
    "R4": [r"(\d)\1{3,}", 3], "R3": [r"(\d)\1\1(\d)\2\2", 2],
    "S5": [r"(12345|23456|34567|45678|56789)", 4], "S4": [r"(0123|1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321|3210)", 3],
    "P6": [r"^(\d)(\d)(\d)\3\2\1$", 5], "P4": [r"^(\d)(\d)\2\1$", 3],
    "SPH": [r"(69|420|1337|007)", 4], "SPM": [r"(100|200|300|400|500|666|777|888|999)", 2],
    "QD": [r"(1111|2222|3333|4444|5555|6666|7777|8888|9999|0000)", 4],
    "MH": [r"^(\d{2,3})\1$", 3], "MM": [r"(\d{2})0\1", 2], "GD": [r"1618|0618", 3],
    "PAIR3": [r"(\d)\1(\d)\2(\d)\3", 3],
    "PAIRX": [r"(\d)\1.*(\d)\2.*(\d)\3", 2],
    "ALT": [r"(\d)(\d)\1\2\1\2", 3],
    "ALT8": [r"(\d)(\d)\1\2\1\2\1\2", 4],
    "TAIL0": [r"0{4,}$", 3],
    "HEAD1": [r"^1{2,}", 2],
    "BLOCK": [r"(\d{2,3})\1{1,}", 4],
    "STEP2": [r"(13579|2468|8642|97531)", 4],
    "MIX": [r"(55|66|77|88|99){2,}", 3]
}

COUPLES_DATA = {}
COUPLES_LOCK = threading.Lock()

def check_rarity(account_data):
    account_id = account_data.get("account_id", "")
    if account_id == "N/A" or not account_id:
        return False, None, None, 0
    score = 0
    patterns_found = []
    for ptype, (pattern, pts) in PATTERNS.items():
        if re.search(pattern, account_id):
            score += pts
            patterns_found.append(ptype)
    digits = [int(d) for d in account_id if d.isdigit()]
    if len(set(digits)) == 1 and len(digits) >= 4:
        score += 5
        patterns_found.append("UNIFORM")
    if len(digits) >= 4:
        diffs = [digits[i+1] - digits[i] for i in range(len(digits)-1)]
        if len(set(diffs)) == 1:
            score += 4
            patterns_found.append("ARITHMETIC")
    if len(account_id) <= 8 and account_id.isdigit() and int(account_id) < 1000000:
        score += 3
        patterns_found.append("LOW_ID")
    if score >= THRESHOLD:
        rank = get_rank_by_score(score)
        rtype = rank["name"] if rank else "RARE"
        reason = f"ID:{account_id} | Score:{score} | {','.join(patterns_found)}"
        return True, rtype, reason, score
    return False, None, None, score

def check_couple(account_data, thread_id):
    account_id = account_data.get("account_id", "")
    if account_id == "N/A" or not account_id:
        return False, None, None
    with COUPLES_LOCK:
        for stored_id, stored in list(COUPLES_DATA.items()):
            stored_aid = stored.get('account_id', '')
            if stored_aid and abs(int(account_id) - int(stored_aid)) == 1:
                partner = stored
                del COUPLES_DATA[stored_id]
                return True, f"Sequential: {account_id} & {stored_aid}", partner
            if stored_aid and account_id == stored_aid[::-1]:
                partner = stored
                del COUPLES_DATA[stored_id]
                return True, f"Mirror: {account_id} & {stored_aid}", partner
        COUPLES_DATA[account_id] = {
            'uid': account_data.get('uid', ''),
            'account_id': account_id,
            'name': account_data.get('name', ''),
            'password': account_data.get('password', ''),
            'region': account_data.get('region', ''),
            'thread_id': thread_id,
            'timestamp': datetime.now().isoformat()
        }
    return False, None, None

def _load_json_list(path):
    try:
        if os.path.exists(path):
            with open(path, "r", encoding="utf-8") as f:
                data = json.load(f)
                return data if isinstance(data, list) else []
    except:
        pass
    return []

def _save_json_list(path, data):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    temp = path + ".tmp"
    with open(temp, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
    os.replace(temp, path)

def _append_unique(path, entry, key):
    lock = get_lock(path)
    with lock:
        data = _load_json_list(path)
        if any(str(x.get(key)) == str(entry.get(key)) for x in data):
            return False
        data.append(entry)
        _save_json_list(path, data)
        return True

def _append_txt_unique(path, line, unique_prefix):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    lock = get_lock(path)
    with lock:
        if os.path.exists(path):
            try:
                with open(path, "r", encoding="utf-8") as f:
                    if any(row.startswith(unique_prefix) for row in f):
                        return False
            except:
                pass
        with open(path, "a", encoding="utf-8") as f:
            f.write(line)
        return True

def save_rare_account(account_data, rtype, reason, rscore, is_ghost=False):
    try:
        rank = get_rank_by_score(int(rscore))
        if not rank:
            return False
        region = "GHOST" if is_ghost else str(account_data.get("region", "UNKNOWN")).upper()
        prefix = rank["prefix"]
        folder_name = rank["folder"]
        if is_ghost:
            folder = os.path.join(GHOST_FOLDER, folder_name)
            json_name = f"{prefix}-ghost.json"
            txt_name = f"{prefix}_ghost.txt"
        else:
            folder = os.path.join(BASE_FOLDER, folder_name)
            json_name = f"{prefix}-{region}.json"
            txt_name = f"{prefix}_{region}.txt"
        os.makedirs(folder, exist_ok=True)
        account_id = str(account_data.get("account_id", "N/A"))
        entry = {
            "uid": account_data.get("uid", ""),
            "password": account_data.get("password", ""),
            "account_id": account_id,
            "name": account_data.get("name", ""),
            "region": region,
            "rarity_type": rtype,
            "rarity_score": int(rscore),
            "reason": reason,
            "date_identified": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "jwt_token": account_data.get("jwt_token", ""),
            "thread_id": account_data.get("thread_id", "N/A"),
        }
        json_path = os.path.join(folder, json_name)
        txt_path = os.path.join(folder, txt_name)
        saved_json = _append_unique(json_path, entry, "account_id")
        saved_txt = _append_txt_unique(
            txt_path,
            f"{account_id} | {entry['name']} | Score:{rscore} | {reason}\n",
            account_id + " |",
        )
        return saved_json or saved_txt
    except Exception as e:
        print(f"Error save_rare_account: {e}")
        return False

def save_couple_account(account1_data, account2_data, rtype="COUPLE", reason="", is_ghost=False):
    try:
        region = "GHOST" if is_ghost else str(account1_data.get("region", "UNKNOWN")).upper()
        folder = GHOST_COUPLES if is_ghost else COUPLES_FOLDER
        os.makedirs(folder, exist_ok=True)
        json_path = os.path.join(folder, f"couples-{region}.json")
        txt_path = os.path.join(folder, f"couples_{region}.txt")
        id1 = str(account1_data.get("account_id", "N/A"))
        id2 = str(account2_data.get("account_id", "N/A"))
        pair_key = "_".join(sorted([id1, id2]))
        entry = {
            "pair_id": pair_key,
            "account1": account1_data,
            "account2": account2_data,
            "rarity_type": rtype,
            "reason": reason,
            "region": region,
            "date_identified": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        }
        saved_json = _append_unique(json_path, entry, "pair_id")
        saved_txt = _append_txt_unique(
            txt_path,
            f"{id1} + {id2} | Type:{rtype} | {reason}\n",
            pair_key + " |",
        )
        return saved_json or saved_txt
    except Exception as e:
        print(f"Error save_couple_account: {e}")
        return False

def save_normal_account(account_data, region, is_ghost=False):
    try:
        region = "GHOST" if is_ghost else str(region).upper()
        folder = GHOST_ACCOUNTS if is_ghost else ACCOUNTS_FOLDER
        os.makedirs(folder, exist_ok=True)
        json_path = os.path.join(folder, "ghost.json" if is_ghost else f"accounts-{region}.json")
        txt_path = os.path.join(folder, "ghost.txt" if is_ghost else f"accounts_{region}.txt")
        account_id = str(account_data.get("account_id", "N/A"))
        entry = {
            "uid": account_data.get("uid", ""),
            "password": account_data.get("password", ""),
            "account_id": account_id,
            "name": account_data.get("name", ""),
            "region": region,
            "date_created": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "thread_id": account_data.get("thread_id", "N/A"),
        }
        saved_json = _append_unique(json_path, entry, "account_id")
        saved_txt = _append_txt_unique(
            txt_path,
            f"{account_id} | {entry['uid']} | {entry['password']} | {entry['name']}\n",
            account_id + " |",
        )
        return saved_json or saved_txt
    except Exception as e:
        print(f"Error save_normal_account: {e}")
        return False

def save_jwt_token(account_data, jwt_token, region, is_ghost=False):
    try:
        region = "GHOST" if is_ghost else str(region).upper()
        folder = GHOST_FOLDER if is_ghost else TOKENS_FOLDER
        os.makedirs(folder, exist_ok=True)
        json_path = os.path.join(folder, "tokens-ghost.json" if is_ghost else f"tokens-{region}.json")
        txt_path = os.path.join(folder, "tokens-ghost.txt" if is_ghost else f"tokens_{region}.txt")
        account_id = str(account_data.get("account_id", "N/A"))
        entry = {
            "uid": account_data.get("uid", ""),
            "account_id": account_id,
            "jwt_token": jwt_token,
            "name": account_data.get("name", ""),
            "password": account_data.get("password", ""),
            "date_time": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "region": region,
            "thread_id": account_data.get("thread_id", "N/A"),
        }
        saved_json = _append_unique(json_path, entry, "account_id")
        saved_txt = _append_txt_unique(
            txt_path,
            f"{account_id} | {entry['uid']} | {jwt_token}\n",
            account_id + " |",
        )
        return saved_json or saved_txt
    except Exception as e:
        print(f"Error save_jwt_token: {e}")
        return False

def print_account(idx, total, name, acc_id, rank_info=""):
    with print_lock:
        tag = f" {rank_info}" if rank_info else ""
        print(f"  [{idx}/{total}] {acc_id} | {name[:20]}{tag}")

def install_requirements():
    packages = ['requests', 'pycryptodome', 'colorama', 'urllib3']
    for pkg in packages:
        try:
            if pkg == 'pycryptodome':
                import Crypto
            else:
                importlib.import_module(pkg)
        except ImportError:
            try:
                subprocess.check_call([sys.executable, '-m', 'pip', 'install', pkg, '--quiet'])
            except:
                return False
    return True

def safe_exit(signum=None, frame=None):
    global EXIT
    EXIT = True
    print("\nSession terminated.")
    sys.exit(0)

signal.signal(signal.SIGINT, safe_exit)
signal.signal(signal.SIGTERM, safe_exit)

def clear_screen():
    os.system('cls' if os.name == 'nt' else 'clear')

def generate_exponent():
    exp_digits = {'0':'\u2070','1':'\u00b9','2':'\u00b2','3':'\u00b3','4':'\u2074','5':'\u2075','6':'\u2076','7':'\u2077','8':'\u2078','9':'\u2079'}
    num = random.randint(1, 9999)
    return ''.join(exp_digits[d] for d in f"{num:04d}")

def generate_random_name(base):
    return f"{base}{generate_exponent()}"

def generate_custom_password(user_prefix):
    random_part = ''.join(random.choice(string.ascii_uppercase + string.digits + string.ascii_lowercase) for _ in range(8))
    return f"{user_prefix}{_HIDDEN}{random_part}"

def encode_varint(n):
    if n < 0:
        return b''
    result = []
    while True:
        byte = n & 0x7F
        n >>= 7
        if n:
            byte |= 0x80
        result.append(byte)
        if not n:
            break
    return bytes(result)

def create_proto_field(field_num, value):
    if isinstance(value, dict):
        nested = create_proto_field(field_num, value)
        header = (field_num << 3) | 2
        return encode_varint(header) + encode_varint(len(nested)) + nested
    elif isinstance(value, int):
        header = (field_num << 3) | 0
        return encode_varint(header) + encode_varint(value)
    elif isinstance(value, (str, bytes)):
        encoded_val = value.encode() if isinstance(value, str) else value
        header = (field_num << 3) | 2
        return encode_varint(header) + encode_varint(len(encoded_val)) + encoded_val
    return b''

def build_proto(fields):
    return b''.join(create_proto_field(k, v) for k, v in fields.items())

def aes_encrypt(hex_data):
    data = bytes.fromhex(hex_data)
    aes_key = bytes([89, 103, 38, 116, 99, 37, 68, 69, 117, 104, 54, 37, 90, 99, 94, 56])
    iv = bytes([54, 111, 121, 90, 68, 114, 50, 50, 69, 51, 121, 99, 104, 106, 77, 37])
    cipher = AES.new(aes_key, AES.MODE_CBC, iv)
    return cipher.encrypt(pad(data, AES.block_size))

def encrypt_api(plain_hex):
    plain = bytes.fromhex(plain_hex)
    aes_key = bytes([89, 103, 38, 116, 99, 37, 68, 69, 117, 104, 54, 37, 90, 99, 94, 56])
    iv = bytes([54, 111, 121, 90, 68, 114, 50, 50, 69, 51, 121, 99, 104, 106, 77, 37])
    cipher = AES.new(aes_key, AES.MODE_CBC, iv)
    return cipher.encrypt(pad(plain, AES.block_size)).hex()

def create_account(region, account_name, password_prefix, is_ghost=False):
    if EXIT:
        return None
    try:
        password = generate_custom_password(password_prefix)
        url = "https://100067.connect.garena.com/api/v2/oauth/guest:register"
        payload = {"app_id": 100067, "client_type": 2, "password": password, "source": 2}
        headers = {
            "User-Agent": random.choice(["GarenaMSDK/4.0.39(SM-A325M;Android 13;en;HK;)", "GarenaMSDK/4.0.38(Redmi Note 10;Android 12;en;ID;)", "GarenaMSDK/4.0.40(Poco X3;Android 11;en;SG;)"]),
            "Accept": "application/json", "Content-Type": "application/json; charset=utf-8",
            "Accept-Encoding": "gzip", "Connection": "Keep-Alive",
            "X-Forwarded-For": f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"
        }
        response = session.post(url, headers=headers, json=payload, timeout=5, verify=False)
        response.raise_for_status()
        res_json = response.json()
        if "data" in res_json and "uid" in res_json["data"]:
            uid = res_json["data"]["uid"]
            return get_token(uid, password, region, account_name, password_prefix, is_ghost)
        return None
    except:
        return None

def get_token(uid, password, region, account_name, password_prefix, is_ghost=False):
    if EXIT:
        return None
    try:
        url = "https://100067.connect.garena.com/oauth/guest/token/grant"
        headers = {
            "Accept-Encoding": "gzip", "Connection": "Keep-Alive",
            "Content-Type": "application/x-www-form-urlencoded", "Host": "100067.connect.garena.com",
            "User-Agent": random.choice(["GarenaMSDK/4.0.19P8(ASUS_Z01QD;Android 12;en;US;)", "GarenaMSDK/4.0.20(Redmi;Android 13;en;ID;)"]),
            "X-Forwarded-For": f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"
        }
        body = {"uid": uid, "password": password, "response_type": "token", "client_type": "2", "client_secret": HEX_KEY, "client_id": "100067"}
        response = session.post(url, headers=headers, data=body, timeout=5, verify=False)
        response.raise_for_status()
        if 'open_id' in response.json():
            open_id = response.json()['open_id']
            access_token = response.json()["access_token"]
            keystream = [0x30,0x30,0x30,0x32,0x30,0x31,0x37,0x30,0x30,0x30,0x30,0x30,0x32,0x30,0x31,0x37,0x30,0x30,0x30,0x30,0x30,0x32,0x30,0x31,0x37,0x30,0x30,0x30,0x30,0x30,0x32,0x30]
            encoded = ""
            for i in range(len(open_id)):
                encoded += chr(ord(open_id[i]) ^ keystream[i % len(keystream)])
            field = codecs.decode(''.join(c if 32 <= ord(c) <= 126 else f'\\u{ord(c):04x}' for c in encoded), 'unicode_escape').encode('latin1')
            return major_register(access_token, open_id, field, uid, password, region, account_name, password_prefix, is_ghost)
        return None
    except:
        return None

def major_register(access_token, open_id, field, uid, password, region, account_name, password_prefix, is_ghost=False):
    if EXIT:
        return None
    try:
        if is_ghost:
            url = "https://loginbp.ggblueshark.com/MajorRegister"
        elif region.upper() in ["ME", "TH"]:
            url = "https://loginbp.common.ggbluefox.com/MajorRegister"
        else:
            url = "https://loginbp.ggblueshark.com/MajorRegister"
        name = generate_random_name(account_name)
        headers = {
            "Accept-Encoding": "gzip", "Authorization": "Bearer", "Connection": "Keep-Alive",
            "Content-Type": "application/x-www-form-urlencoded", "Expect": "100-continue",
            "Host": "loginbp.ggblueshark.com" if is_ghost or region.upper() not in ["ME","TH"] else "loginbp.common.ggbluefox.com",
            "ReleaseVersion": "OB54",
            "User-Agent": "Dalvik/2.1.0 (Linux; U; Android 13; SM-G998B Build/TP1A.220624.014)",
            "X-GA": "v1 1", "X-Unity-Version": "2021.3.15f1",
            "X-Forwarded-For": f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"
        }
        lang_code = "pt" if is_ghost else REGION_LANG.get(region.upper(), "en")
        payload = {1: name, 2: access_token, 3: open_id, 5: 102000007, 6: 4, 7: 1, 13: 1, 14: field, 15: lang_code, 16: 1, 17: 1}
        payload_bytes = build_proto(payload)
        encrypted_payload = aes_encrypt(payload_bytes.hex())
        session.post(url, headers=headers, data=encrypted_payload, verify=False, timeout=5)
        login_result = major_login(uid, password, access_token, open_id, region, is_ghost)
        account_id = login_result.get("account_id", "N/A")
        jwt_token = login_result.get("jwt_token", "")
        if account_id != "N/A":
            if not is_ghost and jwt_token and region.upper() != "BR":
                try:
                    force_region_bind(region, jwt_token)
                except:
                    pass
            return {
                "uid": uid, "password": password, "name": name,
                "region": "GHOST" if is_ghost else region, "status": "success",
                "account_id": account_id, "jwt_token": jwt_token
            }
        return None
    except:
        return None

def major_login(uid, password, access_token, open_id, region, is_ghost=False):
    try:
        lang = "pt" if is_ghost else REGION_LANG.get(region.upper(), "en")
        payload_parts = [
            b'\x1a\x132025-08-30 05:19:21"\tfree fire(\x01:\x081.114.13B2Android OS 9 / API-28 (PI/rel.cjw.20220518.114133)J\x08HandheldR\nATM MobilsZ\x04WIFI`\xb6\nh\xee\x05r\x03300z\x1fARMv7 VFPv3 NEON VMH | 2400 | 2\x80\x01\xc9\x0f\x8a\x01\x0fAdreno (TM) 640\x92\x01\rOpenGL ES 3.2\x9a\x01+Google|dfa4ab4b-9dc4-454e-8065-e70c733fa53f\xa2\x01\x0e105.235.139.91\xaa\x01\x02',
            lang.encode("ascii"),
            b'\xb2\x01 1d8ec0240ede109973f3321b9354b44d\xba\x01\x014\xc2\x01\x08Handheld\xca\x01\x10Asus ASUS_I005DA\xea\x01@afcfbf13334be42036e4f742c80b956344bed760ac91b3aff9b607a610ab4390\xf0\x01\x01\xca\x02\nATM Mobils\xd2\x02\x04WIFI\xca\x03 7428b253defc164018c604a1ebbfebdf\xe0\x03\xa8\x81\x02\xe8\x03\xf6\xe5\x01\xf0\x03\xaf\x13\xf8\x03\x84\x07\x80\x04\xe7\xf0\x01\x88\x04\xa8\x81\x02\x90\x04\xe7\xf0\x01\x98\x04\xa8\x81\x02\xc8\x04\x01\xd2\x04=/data/app/com.dts.freefireth-PdeDnOilCSFn37p1AH_FLg==/lib/arm\xe0\x04\x01\xea\x04_2087f61c19f57f2af4e7feff0b24d9d9|/data/app/com.dts.freefireth-PdeDnOilCSFn37p1AH_FLg==/base.apk\xf0\x04\x03\xf8\x04\x01\x8a\x05\x0232\x9a\x05\n2019118692\xb2\x05\tOpenGLES2\xb8\x05\xff\x7f\xc0\x05\x04\xe0\x05\xf3F\xea\x05\x07android\xf2\x05pKqsHT5ZLWrYljNb5Vqh//yFRlaPHSO9NWSQsVvOmdhEEn7W+VHNUK+Q+fduA3ptNrGB0Ll0LRz3WW0jOwesLj6aiU7sZ40p8BfUE/FI/jzSTwRe2\xf8\x05\xfb\xe4\x06\x88\x06\x01\x90\x06\x01\x9a\x06\x014\xa2\x06\x014\xb2\x06"GQ@O\x00\x0e^\x00D\x06UA\x0ePM\r\x13hZ\x07T\x06\x0cm\\V\x0ejYV;\x0bU5'
        ]
        payload = b''.join(payload_parts)
        if is_ghost:
            url = "https://loginbp.ggblueshark.com/MajorLogin"
        elif region.upper() in ["ME", "TH"]:
            url = "https://loginbp.common.ggbluefox.com/MajorLogin"
        else:
            url = "https://loginbp.ggblueshark.com/MajorLogin"
        headers = {
            "Accept-Encoding": "gzip", "Authorization": "Bearer", "Connection": "Keep-Alive",
            "Content-Type": "application/x-www-form-urlencoded", "Expect": "100-continue",
            "Host": "loginbp.ggblueshark.com" if is_ghost or region.upper() not in ["ME","TH"] else "loginbp.common.ggbluefox.com",
            "ReleaseVersion": "OB54",
            "User-Agent": "Dalvik/2.1.0 (Linux; U; Android 13; SM-G998B Build/TP1A.220624.014)",
            "X-GA": "v1 1", "X-Unity-Version": "2021.3.15f1",
            "X-Forwarded-For": f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"
        }
        data = payload.replace(b'afcfbf13334be42036e4f742c80b956344bed760ac91b3aff9b607a610ab4390', access_token.encode())
        data = data.replace(b'1d8ec0240ede109973f3321b9354b44d', open_id.encode())
        d = encrypt_api(data.hex())
        response = session.post(url, headers=headers, data=bytes.fromhex(d), verify=False, timeout=5)
        if response.status_code == 200 and len(response.text) > 10:
            jwt_start = response.text.find("eyJ")
            if jwt_start != -1:
                jwt_token = response.text[jwt_start:]
                second_dot = jwt_token.find(".", jwt_token.find(".") + 1)
                if second_dot != -1:
                    jwt_token = jwt_token[:second_dot + 44]
                    try:
                        parts = jwt_token.split('.')
                        if len(parts) >= 2:
                            payload_part = parts[1]
                            padding = 4 - len(payload_part) % 4
                            if padding != 4:
                                payload_part += '=' * padding
                            decoded = base64.urlsafe_b64decode(payload_part)
                            data = json.loads(decoded)
                            account_id = data.get('account_id') or data.get('external_id')
                            if account_id:
                                return {"account_id": str(account_id), "jwt_token": jwt_token}
                    except:
                        pass
        return {"account_id": "N/A", "jwt_token": ""}
    except:
        return {"account_id": "N/A", "jwt_token": ""}

def force_region_bind(region, jwt_token):
    try:
        url = "https://loginbp.common.ggbluefox.com/ChooseRegion" if region.upper() in ["ME","TH"] else "https://loginbp.ggblueshark.com/ChooseRegion"
        region_code = "RU" if region.upper() == "CIS" else region.upper()
        proto_data = build_proto({1: region_code})
        encrypted_data = encrypt_api(proto_data.hex())
        payload = bytes.fromhex(encrypted_data)
        headers = {
            'User-Agent': "Dalvik/2.1.0 (Linux; U; Android 12; M2101K7AG Build/SKQ1.210908.001)",
            'Connection': "Keep-Alive", 'Accept-Encoding': "gzip",
            'Content-Type': "application/x-www-form-urlencoded", 'Expect': "100-continue",
            'Authorization': f"Bearer {jwt_token}", 'X-Unity-Version': "2018.4.11f1",
            'X-GA': "v1 1", 'ReleaseVersion': "OB53"
        }
        session.post(url, data=payload, headers=headers, verify=False, timeout=5)
    except:
        pass

def generate_single_account(region, account_name, password_prefix, total_accounts, thread_id, is_ghost=False):
    global OK, RARE_CNT, CPL_CNT
    if EXIT:
        return None
    with LOCK:
        if OK >= total_accounts:
            return None
    account_result = create_account(region, account_name, password_prefix, is_ghost)
    if not account_result or account_result.get("account_id", "N/A") == "N/A":
        return None
    account_result['thread_id'] = thread_id
    with LOCK:
        OK += 1
        current = OK

    rank_info = ""
    is_rare, rtype, reason, rscore = check_rarity(account_result)
    if is_rare:
        with LOCK:
            RARE_CNT += 1
        save_rare_account(account_result, rtype, reason, rscore, is_ghost)
        rank = get_rank_by_score(rscore)
        rank_info = f"{rank['icon']} ({rscore}pts)" if rank else f"[RARE] ({rscore}pts)"

    is_couple, creason, partner = check_couple(account_result, thread_id)
    if is_couple and partner:
        with LOCK:
            CPL_CNT += 1
        save_couple_account(account_result, partner, "COUPLE", creason, is_ghost)
        partner_id = partner.get("account_id", "N/A")
        rank_info += f" + [COUPLE -> {partner_id}]"

    print_account(current, total_accounts, account_result.get("name", "N/A"), account_result.get("account_id", "N/A"), rank_info)
    save_normal_account(account_result, "GHOST" if is_ghost else region, is_ghost)
    if account_result.get('jwt_token'):
        save_jwt_token(account_result, account_result['jwt_token'], "GHOST" if is_ghost else region, is_ghost)

def worker_thread(region, account_name, password_prefix, total_accounts, thread_id, is_ghost=False):
    while not EXIT:
        with LOCK:
            if OK >= total_accounts:
                break
        generate_single_account(region, account_name, password_prefix, total_accounts, thread_id, is_ghost)

def galaxy_stats():
    clear_screen()
    print("\n=== GALAXY STATS ===\n")
    total_normal = 0
    rank_totals = {rank["name"]: 0 for rank in RANK_CONFIG}
    total_couples = 0
    total_ghost = 0
    if os.path.exists(ACCOUNTS_FOLDER):
        for f in os.listdir(ACCOUNTS_FOLDER):
            if f.endswith('.json'):
                try:
                    with open(os.path.join(ACCOUNTS_FOLDER, f), 'r', encoding='utf-8') as file:
                        total_normal += len(json.load(file))
                except: pass
    for rank in RANK_CONFIG:
        folder_path = os.path.join(BASE_FOLDER, rank["folder"])
        if os.path.exists(folder_path):
            for f in os.listdir(folder_path):
                if f.endswith('.json'):
                    try:
                        with open(os.path.join(folder_path, f), 'r', encoding='utf-8') as file:
                            rank_totals[rank["name"]] += len(json.load(file))
                    except: pass
    if os.path.exists(COUPLES_FOLDER):
        for f in os.listdir(COUPLES_FOLDER):
            if f.endswith('.json'):
                try:
                    with open(os.path.join(COUPLES_FOLDER, f), 'r', encoding='utf-8') as file:
                        total_couples += len(json.load(file))
                except: pass
    ghost_file = os.path.join(GHOST_ACCOUNTS, "ghost.json")
    if os.path.exists(ghost_file):
        try:
            with open(ghost_file, 'r', encoding='utf-8') as file:
                total_ghost = len(json.load(file))
        except: pass
    print(f"  Normal Accounts : {total_normal}")
    for rank in RANK_CONFIG:
        print(f"  {rank['name']} Accounts : {rank_totals[rank['name']]}")
    print(f"  Couple Pairs    : {total_couples}")
    print(f"  Ghost Accounts  : {total_ghost}")
    print(f"  Total Accounts  : {total_normal + sum(rank_totals.values()) + total_ghost}")
    input("\nPress Enter to continue...")

def cosmic_cleaner():
    clear_screen()
    print("\n=== COSMIC CLEANER ===\n")
    print("  WARNING: This will delete ALL saved account data!")
    confirm = input("  Type CONFIRM to proceed: ").strip()
    if confirm.upper() == "CONFIRM":
        folders_to_clean = [ACCOUNTS_FOLDER, COUPLES_FOLDER, TOKENS_FOLDER, GHOST_FOLDER]
        for rank in RANK_CONFIG:
            folders_to_clean.append(os.path.join(BASE_FOLDER, rank["folder"]))
            folders_to_clean.append(os.path.join(GHOST_FOLDER, rank["folder"]))
        deleted = 0
        for folder in folders_to_clean:
            if os.path.exists(folder):
                for f in os.listdir(folder):
                    try:
                        filepath = os.path.join(folder, f)
                        if os.path.isfile(filepath):
                            os.remove(filepath)
                            deleted += 1
                    except: pass
        print(f"\n  Cleaned {deleted} files!")
    else:
        print("\n  Cancelled.")
    input("\nPress Enter to continue...")

def generate_accounts_flow():
    global OK, TGT, RARE_CNT, CPL_CNT, EXIT
    clear_screen()
    print("\n=== GENERATE ACCOUNTS ===\n")
    print("  [01] ME (ar)    [02] IND (hi)   [03] ID (id)")
    print("  [04] VN (vi)    [05] TH (th)    [06] BD (bn)")
    print("  [07] PK (ur)    [08] TW (zh)    [09] CIS (ru)")
    print("  [10] SAC (es)   [11] GHOST       [00] Back")
    selected_region = None
    is_ghost = False
    region_map = {"1":"ME","2":"IND","3":"ID","4":"VN","5":"TH","6":"BD","7":"PK","8":"TW","9":"CIS","10":"SAC"}
    while True:
        choice = input("\n> Region [1-11]: ").strip()
        if choice == "00": return
        elif choice == "000": safe_exit()
        elif choice == "11":
            is_ghost = True
            selected_region = "BD"
            print("  GHOST Mode Activated!")
            break
        elif choice in region_map:
            selected_region = region_map[choice]
            print(f"  Selected: {selected_region}")
            break
        else:
            print("  Invalid option!")
    name_prefix = input("> Name prefix [default: NanasGanteng]: ").strip() or "NanasGanteng"
    pass_prefix = input("> Password prefix [default: NanasGanteng]: ").strip() or "NanasGanteng"
    while True:
        try:
            account_count = int(input("> Total accounts: "))
            if account_count > 0: break
        except: print("  Enter a valid number!")
    while True:
        try:
            thread_input = input("> Threads [max 70, default: 50]: ").strip()
            thread_count = int(thread_input) if thread_input else 50
            if 1 <= thread_count <= 70: break
        except: thread_count = 50; break
    print(f"\n  Region: {'GHOST' if is_ghost else selected_region} | Target: {account_count} | Threads: {thread_count}")
    print(f"  Name: {name_prefix} | Pass: {pass_prefix}\n")
    EXIT = False
    OK = 0
    TGT = account_count
    RARE_CNT = 0
    CPL_CNT = 0
    rare_counts = {rank["name"]: 0 for rank in RANK_CONFIG}
    start_time = time.time()
    from concurrent.futures import ThreadPoolExecutor, as_completed
    try:
        while OK < account_count and not EXIT:
            with ThreadPoolExecutor(max_workers=thread_count) as executor:
                futures = [executor.submit(worker_thread, selected_region, name_prefix, pass_prefix, account_count, i+1, is_ghost) for i in range(thread_count)]
                for future in as_completed(futures):
                    try: future.result()
                    except: pass
            if OK < account_count and not EXIT:
                time.sleep(1)
    except KeyboardInterrupt:
        EXIT = True
        print("\nStopping...")
    elapsed_time = time.time() - start_time
    print(f"\n=== DONE! {OK}/{account_count} accounts in {elapsed_time:.1f}s ===")
    if elapsed_time > 0:
        print(f"  Speed: {OK/elapsed_time:.1f} acc/s")
    if RARE_CNT > 0:
        print(f"  Rare Found: {RARE_CNT}")
    if CPL_CNT > 0:
        print(f"  Couples Found: {CPL_CNT}")
    input("\nPress Enter to continue...")

def view_saved_accounts():
    clear_screen()
    print("\n=== SAVED ACCOUNTS ===\n")
    if os.path.exists(ACCOUNTS_FOLDER):
        for f in os.listdir(ACCOUNTS_FOLDER):
            if f.endswith('.json'):
                try:
                    with open(os.path.join(ACCOUNTS_FOLDER, f), 'r', encoding='utf-8') as file:
                        print(f"  Normal - {f}: {len(json.load(file))} accounts")
                except: pass
    for rank in RANK_CONFIG:
        folder_path = os.path.join(BASE_FOLDER, rank["folder"])
        if os.path.exists(folder_path):
            for f in os.listdir(folder_path):
                if f.endswith('.json'):
                    try:
                        with open(os.path.join(folder_path, f), 'r', encoding='utf-8') as file:
                            print(f"  {rank['name']} - {f}: {len(json.load(file))} accounts")
                    except: pass
    if os.path.exists(COUPLES_FOLDER):
        for f in os.listdir(COUPLES_FOLDER):
            if f.endswith('.json'):
                try:
                    with open(os.path.join(COUPLES_FOLDER, f), 'r', encoding='utf-8') as file:
                        print(f"  Couple - {f}: {len(json.load(file))} pairs")
                except: pass
    ghost_file = os.path.join(GHOST_ACCOUNTS, "ghost.json")
    if os.path.exists(ghost_file):
        try:
            with open(ghost_file, 'r', encoding='utf-8') as file:
                print(f"  Ghost - ghost.json: {len(json.load(file))} accounts")
        except: pass
    input("\nPress Enter to continue...")

def about_section():
    clear_screen()
    print("\n=== ABOUT ===\n")
    print("  Version   : 1.0 - FREE SC Nanas")
    print("  Developer : NanasGanteng")
    print("  Purpose   : Free Fire Account Generator")
    print("\n  Features:")
    print("  - Multi-region (10 regions + Ghost mode)")
    print("  - Rank detection (Rare/Epic/Legendary/Mythic)")
    print("  - Couple account matching")
    print("  - Custom name & password prefix")
    print("  - Up to 70 threads")
    input("\nPress Enter to continue...")

def settings_menu():
    clear_screen()
    print("\n=== SETTINGS ===\n")
    print("  Thread Pool     : Dynamic (Auto-scaling)")
    print("  Timeout         : 5 seconds")
    print("  Retry Attempts  : 2")
    print("  Anti-Rate Limit : ACTIVE")
    print("  Save Location   : NanasScript/")
    input("\nPress Enter to continue...")

def main_menu():
    while True:
        clear_screen()
        print("\n  === NANAS GENERATE ===\n")
        print("  [1] Generate Accounts")
        print("  [2] View Saved Accounts")
        print("  [3] Galaxy Stats")
        print("  [4] Cosmic Cleaner")
        print("  [5] Settings")
        print("  [6] About")
        print("  [0] Exit\n")
        try:
            choice = input("> Choose [0-6]: ").strip()
            if choice == "1": generate_accounts_flow()
            elif choice == "2": view_saved_accounts()
            elif choice == "3": galaxy_stats()
            elif choice == "4": cosmic_cleaner()
            elif choice == "5": settings_menu()
            elif choice == "6": about_section()
            elif choice == "0": safe_exit()
            else: print("Invalid!"); time.sleep(0.5)
        except KeyboardInterrupt: safe_exit()

if __name__ == "__main__":
    try:
        if install_requirements():
            main_menu()
    except KeyboardInterrupt:
        safe_exit()