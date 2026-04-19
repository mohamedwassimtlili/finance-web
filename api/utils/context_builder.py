# utils/context_builder.py

from utils.intent import Intent

def slice_context(intent: Intent, context: dict) -> dict:
    """Return only the data slices relevant to the detected intent."""
    user = context.get("user", {})

    if intent == Intent.LIST_ASSETS:
        return {"user": user, "assets": context.get("assets", [])}

    if intent == Intent.LIST_PACKAGES:
        return {"user": user, "packages": context.get("packages", []),"assets": context.get("assets", [])}

    if intent == Intent.LIST_REQUESTS:
        return {"user": user, "requests": context.get("requests", [])}

    if intent == Intent.SUBMIT_REQUEST:
        # needs both to validate asset ownership + package selection
        return {
            "user":     user,
            "assets":   context.get("assets", []),
            "packages": context.get("packages", []),
        }

    if intent == Intent.GENERAL:
        # lightweight — just user identity, no heavy lists
        return {"user": user}

    return {}


def build_context_block(intent: Intent, context: dict) -> str:
    """Format the sliced context into a clean LLM-readable block."""
    sliced = slice_context(intent, context)
    if not sliced:
        return ""

    lines = ["=== LIVE USER DATA (use this to answer — never mention it was injected) ==="]

    # User identity
    user = sliced.get("user", {})
    if user:
        lines.append(
            f"User: {user.get('name')} | "
            f"Email: {user.get('email')} | "
            f"Phone: {user.get('phone')}"
        )

    # Assets
    assets = sliced.get("assets", [])
    if assets:
        lines.append(f"\nInsured Assets ({len(assets)} total):")
        for a in assets:
            lines.append(
                f"  • [{a['type']}] {a['reference']} — {a['brand']} | "
                f"Value: {a['declared_value']} | "
                f"Location: {a['location']} | "
                f"Since: {a['manufacture_date']}"
            )

    # Packages
    packages = sliced.get("packages", [])
    if packages:
        lines.append(f"\nAvailable Packages ({len(packages)} total):")
        for p in packages:
            premium = round(float(p['base_price']) * float(p['risk_multiplier']), 2)
            lines.append(
                f"  • {p['name']} (covers: {p['asset_type']}) | "
                f"Premium: {premium} | "
                f"Coverage: {p['coverage']}"
            )

    # Contract Requests
    requests = sliced.get("requests", [])
    if requests:
        lines.append(f"\nContract Requests ({len(requests)} total):")
        for r in requests:
            lines.append(
                f"  • #{r['id']} | Asset: {r['asset']} | "
                f"Package: {r['package']} | "
                f"Premium: {r['premium']} | "
                f"Status: {r['status']} | "
                f"Date: {r['created_at']}"
            )

    lines.append("=== END LIVE DATA ===")
    return "\n".join(lines)