
import json

try:
    with open('public/report/report.json', 'r') as f:
        data = json.load(f)

    print("Analyzing Modules/Common classes...")
    candidates = []

    for class_name, metrics in data.items():
        if "Modules\\Common" in class_name:
            ccn = metrics.get('ccn', 0)
            mi = metrics.get('mi', 100)
            
            # Simple scoring: Higher CCN is bad, Lower MI is bad.
            # We want to lower CCN and Raise MI.
            
            if ccn > 4 or mi < 85:
                candidates.append({
                    'name': class_name,
                    'ccn': ccn,
                    'mi': mi,
                    'file': metrics.get('filename') # Might not be available in all reports
                })

    # Sort by CCN descending
    candidates.sort(key=lambda x: x['ccn'], reverse=True)

    print(f"Found {len(candidates)} candidates.")
    for c in candidates[:10]:
        print(f"Class: {c['name']}, CCN: {c['ccn']}, MI: {c['mi']}")

except Exception as e:
    print(f"Error: {e}")
