"""
Parser for Cisco 'show inventory' output.
Extracts model and serial number from the first chassis entry.
"""
import re
from typing import Dict, Any


def parse_show_inventory(output: str) -> Dict[str, Any]:
    """
    Parse show inventory output.
    
    Example:
    NAME: "Chassis", DESCR: "Cisco ISR 4451-X"
    PID: ISR4451-X/K9    , VID: V01, SN: FJC2140D0AB
    """
    result = {
        "model": None,
        "serial_number": None,
        "description": None,
    }

    if not output:
        return result

    lines = output.strip().splitlines()

    for i, line in enumerate(lines):
        # Look for the first NAME/DESCR line (usually chassis)
        name_match = re.match(r'NAME:\s*"(.+?)".*DESCR:\s*"(.+?)"', line)
        if name_match:
            name = name_match.group(1)
            descr = name_match.group(2)

            # Check next line for PID and SN
            if i + 1 < len(lines):
                pid_line = lines[i + 1]
                pid_match = re.search(r"PID:\s*(\S+)", pid_line)
                sn_match = re.search(r"SN:\s*(\S+)", pid_line)

                if pid_match:
                    result["model"] = pid_match.group(1)
                if sn_match:
                    result["serial_number"] = sn_match.group(1)
                result["description"] = descr

            # Only take the first (chassis) entry
            if result["serial_number"]:
                break

    return result
