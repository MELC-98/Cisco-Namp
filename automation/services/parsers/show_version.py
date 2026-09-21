"""
Parser for Cisco 'show version' output.
Extracts hostname, model, OS version, uptime, serial number, etc.
"""
import re
from typing import Dict, Any, Optional


def parse_show_version(output: str) -> Dict[str, Any]:
    """Parse show version output and return structured facts."""
    facts: Dict[str, Any] = {
        "hostname": None,
        "model": None,
        "serial_number": None,
        "os_name": None,
        "os_version": None,
        "uptime": None,
        "hardware": None,
        "image_file": None,
    }

    if not output:
        return facts

    lines = output.strip().splitlines()

    for line in lines:
        line_stripped = line.strip()

        # Hostname (from the "hostname uptime is" line)
        uptime_match = re.match(r"^(\S+)\s+uptime\s+is\s+(.+)$", line_stripped)
        if uptime_match:
            facts["hostname"] = uptime_match.group(1)
            facts["uptime"] = uptime_match.group(2).strip()
            continue

        # IOS / IOS XE Version
        version_match = re.search(r"(?:Cisco IOS XE Software|Cisco IOS Software).+Version\s+(\S+)", line_stripped)
        if version_match:
            facts["os_version"] = version_match.group(1).rstrip(",")
            if "IOS XE" in line_stripped or "IOS-XE" in line_stripped:
                facts["os_name"] = "IOS XE"
            else:
                facts["os_name"] = "IOS"
            continue

        # Alternative version line
        if "Version" in line_stripped and facts["os_version"] is None:
            alt_match = re.search(r"Version\s+(\S+)", line_stripped)
            if alt_match:
                facts["os_version"] = alt_match.group(1).rstrip(",")

        # Model/Hardware
        if line_stripped.startswith("cisco ") or line_stripped.startswith("Cisco "):
            model_match = re.match(r"[Cc]isco\s+(\S+(?:\s+\S+)?)\s+", line_stripped)
            if model_match and not facts["model"]:
                facts["model"] = model_match.group(1)

        # Processor board ID (serial number)
        serial_match = re.match(r"Processor board ID\s+(\S+)", line_stripped)
        if serial_match:
            facts["serial_number"] = serial_match.group(1)

        # System image file
        image_match = re.match(r'System image file is "(.+)"', line_stripped)
        if image_match:
            facts["image_file"] = image_match.group(1)

        # Hardware
        if "processor" in line_stripped.lower() and "bytes of memory" in line_stripped.lower():
            hw_match = re.match(r"(.+?)\s+processor", line_stripped)
            if hw_match:
                facts["hardware"] = hw_match.group(1).strip()

    return facts
