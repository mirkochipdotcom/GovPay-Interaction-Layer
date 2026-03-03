"""
Fix bug in pysaml2: _filter_values() returns None instead of [] when vals=None.

Root cause:
  SATOSA's _get_approved_attributes() builds:
    all_attributes = {v: None for v in aconv._fro.values()}
  Then pysaml2 calls _filter_values(None, []) which returns None (vals as-is).
  The caller does: res[attr].extend(None) → TypeError: 'NoneType' object is not iterable

  This causes SATOSA to crash on every AuthnRequest with a generic 302 redirect
  to SATOSA_UNKNOW_ERROR_REDIRECT_PAGE, preventing the disco page from receiving
  the correct ?return= parameter, so clicking on IDPs does nothing.

Fix: return [] instead of None when vals is None and vlist is empty/None.
"""
import sys
import glob

# Find the actual pysaml2 path (Python version may vary)
candidates = glob.glob("/.venv/lib/python*/site-packages/saml2/assertion.py")
if not candidates:
    print("ERROR: saml2/assertion.py not found under /.venv - skipping patch")
    sys.exit(0)

path = candidates[0]
print(f"[patch_saml2] Patching {path}")

with open(path) as f:
    content = f.read()

OLD = (
    "    if not vlist:  # No value specified equals any value\n"
    "        return vals\n"
)
NEW = (
    "    if not vlist:  # No value specified equals any value\n"
    "        return vals if vals is not None else []\n"
)

if OLD not in content:
    if "return vals if vals is not None else []" in content:
        print("[patch_saml2] Already patched, nothing to do.")
        sys.exit(0)
    else:
        print("[patch_saml2] WARNING: expected pattern not found — pysaml2 may have changed.")
        print("[patch_saml2] Skipping patch. SSO may not work correctly.")
        sys.exit(0)

content = content.replace(OLD, NEW, 1)
with open(path, "w") as f:
    f.write(content)

print("[patch_saml2] patch applied successfully.")
