"""
Photogrammetry camera position export.

Generates CSV with camera positions and orientations in omega/phi/kappa
Euler angles, compatible with Pix4D, Agisoft Metashape, and other
photogrammetry software.
"""
import io
import math


def generate_photo_positions_csv(flight_plan):
    """Generate photogrammetry camera position CSV.

    Columns: imageName, lat, lng, altitude_m, omega, phi, kappa
    - omega: rotation around X axis (pitch equivalent)
    - phi: rotation around Y axis (roll, typically 0 for drones)
    - kappa: rotation around Z axis (heading/yaw)

    Args:
        flight_plan: FlightPlan model with waypoints

    Returns:
        BytesIO buffer containing CSV data
    """
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)

    headers = ["imageName", "latitude", "longitude", "altitude_m",
               "omega", "phi", "kappa"]
    lines = [",".join(headers)]

    for w in waypoints:
        # Convert gimbal pitch to omega (rotation around X)
        # Gimbal pitch -90 (nadir) = omega 0 (camera pointing straight down)
        # Gimbal pitch -45 = omega 45
        # Gimbal pitch 0 (horizontal) = omega 90
        gimbal = w.gimbal_pitch_deg if w.gimbal_pitch_deg is not None else -90
        omega = 90 + gimbal  # -90 -> 0, -45 -> 45, 0 -> 90

        # Phi is roll, typically 0 for drone missions
        phi = 0.0

        # Kappa is heading/yaw
        heading = w.heading_deg if w.heading_deg is not None else 0
        kappa = heading

        # Image name follows photogrammetry convention
        image_name = f"IMG_{w.index:04d}.JPG"

        row = [
            image_name,
            f"{w.lat:.7f}",
            f"{w.lng:.7f}",
            f"{w.altitude_m:.1f}",
            f"{omega:.2f}",
            f"{phi:.2f}",
            f"{kappa:.2f}",
        ]
        lines.append(",".join(row))

    buf = io.BytesIO("\n".join(lines).encode("utf-8"))
    buf.seek(0)
    return buf
