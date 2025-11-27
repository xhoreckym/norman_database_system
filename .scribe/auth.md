# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer 1|your_token_here"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

**How to get your token:**
1. Log in to the [NORMAN Database System](https://www.norman-network.com/nds/)
2. Go to your **Dashboard** → **API Tokens**
3. Click **Generate New Token**
4. Copy the token (it will only be shown once!)

**Your token looks like this:**
```
1|uQgwNnXHx7QJom31K8dfsdfsd8f7sd98f7sdf
```

---

### "Try It Out" - What to paste in the Authorization field:

Copy your **full token** and paste it in the Authorization box:
```
1|uQgwNnXHx7QJom31K8dfsdfsd8f7sd98f7sdf
```
(Scribe automatically adds "Bearer " prefix)

---

### Code Examples

**curl:**
```bash
curl -H "Authorization: Bearer 1|uQgwNnXHx7QJom31..." \
     "https://www.norman-network.com/nds/api/v1/empodat/country/SK"
```

**Python:**
```python
import requests

headers = {"Authorization": "Bearer 1|uQgwNnXHx7QJom31..."}
response = requests.get(
    "https://www.norman-network.com/nds/api/v1/empodat/country/SK",
    headers=headers
)
```

**JavaScript:**
```javascript
fetch("https://www.norman-network.com/nds/api/v1/empodat/country/SK", {
    headers: { "Authorization": "Bearer 1|uQgwNnXHx7QJom31..." }
})
```
