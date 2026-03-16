"""Comprehensive demo data seeder for FlyingPlan CRM.

Run via:  cd /var/www/html/FlyingPlan && flask seed-demo
"""

from datetime import datetime, timedelta, timezone, date
from extensions import db
from models.user import User
from models.flight_plan import FlightPlan
from models.order import Order
from models.risk_assessment import RiskAssessment
from models.waypoint import Waypoint
from models.poi import POI
from models.order_activity import OrderActivity
from models.order_deliverable import OrderDeliverable
from models.upload import Upload
from models.pilot_certification import PilotCertification
from models.pilot_equipment import PilotEquipment
from models.pilot_membership import PilotMembership
from models.pilot_document import PilotDocument
from models.job_type import JobType, DEFAULT_JOB_TYPES
from models.purpose_option import PurposeOption, DEFAULT_PURPOSE_OPTIONS
from models.heard_about_option import HeardAboutOption, DEFAULT_HEARD_ABOUT_OPTIONS
from models.app_settings import AppSettings


def _now():
    return datetime.now(timezone.utc)


def _ago(**kw):
    """Return a UTC datetime in the past.  e.g. _ago(days=3, hours=2)"""
    return _now() - timedelta(**kw)


def _future(**kw):
    return _now() + timedelta(**kw)


def _date_ago(**kw):
    return (datetime.now(timezone.utc) - timedelta(**kw)).date()


def _date_future(**kw):
    return (datetime.now(timezone.utc) + timedelta(**kw)).date()


# ---------------------------------------------------------------------------
# 1. WIPE
# ---------------------------------------------------------------------------
def _wipe_all():
    """Delete all rows respecting FK order."""
    print("  Wiping existing data...")
    OrderActivity.query.delete()
    OrderDeliverable.query.delete()
    RiskAssessment.query.delete()
    Waypoint.query.delete()
    POI.query.delete()
    Upload.query.delete()
    Order.query.delete()
    FlightPlan.query.delete()
    PilotCertification.query.delete()
    PilotEquipment.query.delete()
    PilotMembership.query.delete()
    PilotDocument.query.delete()
    User.query.delete()
    JobType.query.delete()
    PurposeOption.query.delete()
    HeardAboutOption.query.delete()
    AppSettings.query.delete()
    db.session.commit()


# ---------------------------------------------------------------------------
# 2. LOOKUP TABLES
# ---------------------------------------------------------------------------
def _seed_lookups():
    print("  Seeding lookup tables...")
    AppSettings.get()
    for i, jt in enumerate(DEFAULT_JOB_TYPES):
        db.session.add(JobType(sort_order=i, **jt))
    for i, po in enumerate(DEFAULT_PURPOSE_OPTIONS):
        db.session.add(PurposeOption(sort_order=i, **po))
    for i, ha in enumerate(DEFAULT_HEARD_ABOUT_OPTIONS):
        db.session.add(HeardAboutOption(sort_order=i, **ha))
    db.session.commit()


# ---------------------------------------------------------------------------
# 3. USERS
# ---------------------------------------------------------------------------
def _create_users():
    print("  Creating users...")
    users = {}

    # Admin
    admin = User(
        username="admin",
        display_name="Admin Manager",
        role="admin",
        email="admin@flyingplan.co.uk",
        phone="020 7946 0958",
    )
    admin.set_password("demo123")
    db.session.add(admin)
    users["admin"] = admin

    # --- Pilots ---
    pilot_data = [
        dict(
            username="jmitchell",
            display_name="James Mitchell",
            role="pilot",
            email="james.mitchell@flyingplan.co.uk",
            phone="07700 900123",
            flying_id="CAA-JM-40291",
            operator_id="REOC-UK-88201",
            flying_id_expiry=_date_future(days=280),
            operator_id_expiry=_date_future(days=350),
            insurance_provider="Coverdrone",
            insurance_policy_no="CD-2025-44821",
            insurance_expiry=_date_future(days=200),
            availability_status="available",
            pilot_bio="Senior pilot with 6 years commercial experience. Specialises in construction progress monitoring and agricultural surveys.",
            a2_cofc_expiry=_date_future(days=400),
            gvc_mr_expiry=_date_future(days=350),
            practical_competency_date=_date_ago(days=900),
            mentor_examiner="Capt. Robert Haynes",
            article16_agreed=True,
            article16_agreed_date=_date_ago(days=180),
            address_line1="14 Orchard Lane",
            address_city="Chelmsford",
            address_county="Essex",
            address_postcode="CM1 3QW",
        ),
        dict(
            username="schen",
            display_name="Sarah Chen",
            role="pilot",
            email="sarah.chen@flyingplan.co.uk",
            phone="07700 900456",
            flying_id="CAA-SC-51082",
            operator_id="REOC-UK-77310",
            flying_id_expiry=_date_future(days=190),
            operator_id_expiry=_date_future(days=300),
            insurance_provider="Flock Cover",
            insurance_policy_no="FL-2025-93012",
            insurance_expiry=_date_future(days=150),
            availability_status="available",
            pilot_bio="Mid-level pilot specialising in real estate and architectural photography. GVC certified.",
            a2_cofc_expiry=_date_future(days=300),
            gvc_mr_expiry=_date_future(days=250),
            practical_competency_date=_date_ago(days=500),
            mentor_examiner="Dr. Lisa Tran",
            article16_agreed=True,
            article16_agreed_date=_date_ago(days=120),
            address_line1="28 Willow Drive",
            address_city="Reading",
            address_county="Berkshire",
            address_postcode="RG1 5PQ",
        ),
        dict(
            username="dokonkwo",
            display_name="David Okonkwo",
            role="pilot",
            email="david.okonkwo@flyingplan.co.uk",
            phone="07700 900789",
            flying_id="CAA-DO-62074",
            operator_id="REOC-UK-55420",
            flying_id_expiry=_date_future(days=320),
            operator_id_expiry=_date_future(days=365),
            insurance_provider="Coverdrone",
            insurance_policy_no="CD-2025-67340",
            insurance_expiry=_date_future(days=250),
            availability_status="on_mission",
            pilot_bio="Experienced inspection and survey pilot. Currently operating on-site at Bristol.",
            a2_cofc_expiry=_date_future(days=500),
            gvc_mr_expiry=_date_future(days=400),
            gvc_fw_expiry=_date_future(days=380),
            practical_competency_date=_date_ago(days=700),
            mentor_examiner="Capt. Robert Haynes",
            article16_agreed=True,
            article16_agreed_date=_date_ago(days=200),
            address_line1="5 Maple Terrace",
            address_city="Bristol",
            address_county="Avon",
            address_postcode="BS8 1TH",
        ),
        dict(
            username="ewhitfield",
            display_name="Emma Whitfield",
            role="pilot",
            email="emma.whitfield@flyingplan.co.uk",
            phone="07700 900321",
            flying_id="CAA-EW-73091",
            operator_id="REOC-UK-44280",
            flying_id_expiry=_date_future(days=150),
            operator_id_expiry=_date_future(days=200),
            insurance_provider="Flock Cover",
            insurance_policy_no="FL-2025-10452",
            insurance_expiry=_date_future(days=100),
            availability_status="unavailable",
            pilot_bio="On personal leave until end of month. Experienced in event and celebration coverage.",
            a2_cofc_expiry=_date_future(days=200),
            practical_competency_date=_date_ago(days=400),
            mentor_examiner="Dr. Lisa Tran",
            article16_agreed=True,
            article16_agreed_date=_date_ago(days=90),
            address_line1="91 Church Street",
            address_city="Manchester",
            address_county="Greater Manchester",
            address_postcode="M1 6EU",
        ),
        dict(
            username="rcooper",
            display_name="Ryan Cooper",
            role="pilot",
            email="ryan.cooper@flyingplan.co.uk",
            phone="07700 900654",
            flying_id="CAA-RC-84015",
            operator_id="REOC-UK-33190",
            flying_id_expiry=_date_future(days=340),
            operator_id_expiry=_date_future(days=340),
            insurance_provider="Coverdrone",
            insurance_policy_no="CD-2026-01290",
            insurance_expiry=_date_future(days=330),
            availability_status="available",
            pilot_bio="Newest team member. Keen photographer with A2 CofC. Eager to build experience.",
            a2_cofc_expiry=_date_future(days=350),
            practical_competency_date=_date_ago(days=90),
            mentor_examiner="James Mitchell",
            address_line1="7 Harbour View",
            address_city="Southampton",
            address_county="Hampshire",
            address_postcode="SO14 2AQ",
        ),
    ]

    for pd in pilot_data:
        u = User(**pd)
        u.set_password("demo123")
        db.session.add(u)
        users[pd["username"]] = u

    db.session.flush()  # get IDs

    # --- Certifications ---
    certs = [
        ("jmitchell", "A2 Certificate of Competency", "CAA", "A2-JM-2023-441", _date_ago(days=900), _date_future(days=400)),
        ("jmitchell", "GVC (Multi-Rotor)", "CAA", "GVC-JM-2022-098", _date_ago(days=1000), _date_future(days=350)),
        ("schen", "A2 Certificate of Competency", "CAA", "A2-SC-2024-112", _date_ago(days=500), _date_future(days=300)),
        ("schen", "GVC (Multi-Rotor)", "CAA", "GVC-SC-2024-055", _date_ago(days=400), _date_future(days=250)),
        ("dokonkwo", "A2 Certificate of Competency", "CAA", "A2-DO-2023-330", _date_ago(days=700), _date_future(days=500)),
        ("dokonkwo", "GVC (Multi-Rotor)", "CAA", "GVC-DO-2023-210", _date_ago(days=650), _date_future(days=400)),
        ("dokonkwo", "GVC (Fixed-Wing)", "CAA", "GVC-FW-DO-2023-211", _date_ago(days=600), _date_future(days=380)),
        ("ewhitfield", "A2 Certificate of Competency", "CAA", "A2-EW-2024-201", _date_ago(days=400), _date_future(days=200)),
        ("rcooper", "A2 Certificate of Competency", "CAA", "A2-RC-2025-088", _date_ago(days=90), _date_future(days=350)),
    ]
    for uname, name, body, num, issued, expiry in certs:
        db.session.add(PilotCertification(
            user_id=users[uname].id, cert_name=name, issuing_body=body,
            cert_number=num, issue_date=issued, expiry_date=expiry,
        ))

    # --- Equipment ---
    equip = [
        ("jmitchell", "DJI Mavic 3 Enterprise", "3E4F5G6H7J", "DMAV3-JM-001"),
        ("jmitchell", "DJI Mini 4 Pro", "MN4P-8K9L0M", "DMINI4-JM-002"),
        ("schen", "DJI Air 3", "AIR3-QR2S3T", "DAIR3-SC-001"),
        ("dokonkwo", "DJI Inspire 3", "INS3-UV4W5X", "DINS3-DO-001"),
        ("ewhitfield", "Autel EVO II Pro V3", "EVOII-YZ6A7B", "AEVO2-EW-001"),
        ("rcooper", "DJI Mini 4 Pro", "MN4P-CD8E9F", "DMINI4-RC-001"),
    ]
    for uname, model, serial, reg in equip:
        db.session.add(PilotEquipment(
            user_id=users[uname].id, drone_model=model,
            serial_number=serial, registration_id=reg, is_active=True,
        ))

    # --- Memberships ---
    memberships = [
        ("jmitchell", "FPVUK", "FPVUK-JM-1102", "Full", _date_future(days=200)),
        ("jmitchell", "BMFA", "BMFA-JM-5543", "Country", _date_future(days=180)),
        ("schen", "FPVUK", "FPVUK-SC-2230", "Full", _date_future(days=150)),
        ("dokonkwo", "BMFA", "BMFA-DO-7781", "Full", _date_future(days=250)),
        ("dokonkwo", "FPVUK", "FPVUK-DO-3344", "Full", _date_future(days=260)),
        ("ewhitfield", "FPVUK", "FPVUK-EW-4410", "Associate", _date_future(days=100)),
        ("rcooper", "FPVUK", "FPVUK-RC-5590", "Student", _date_future(days=300)),
    ]
    for uname, org, num, mtype, expiry in memberships:
        db.session.add(PilotMembership(
            user_id=users[uname].id, org_name=org,
            membership_number=num, membership_type=mtype, expiry_date=expiry,
        ))

    db.session.commit()
    return users


# ---------------------------------------------------------------------------
# 4. FLIGHT PLANS + ORDERS
# ---------------------------------------------------------------------------

# Realistic UK coordinates for each order scenario
ORDER_SPECS = [
    # 1 — pending_assignment, construction, Cambridge solar farm
    dict(
        status="pending_assignment", job_type="construction", pilot_key=None,
        customer_name="GreenField Solar Ltd", customer_email="ops@greenfieldsolar.co.uk",
        customer_phone="01223 456789", customer_company="GreenField Solar Ltd",
        location_address="Solar Farm, Barton Road, Cambridge CB3 9LG",
        location_lat=52.1885, location_lng=0.0988, urgency="urgent",
        job_description="Monthly construction progress capture for new 50MW solar farm installation. Client requires ortho-mosaic and progress comparison images.",
        footage_purpose="progress_report", heard_about="google",
    ),
    # 2 — pending_assignment, emergency_insurance, Brighton storm damage
    dict(
        status="pending_assignment", job_type="emergency_insurance", pilot_key=None,
        customer_name="Coastal Property Claims", customer_email="claims@coastalprop.co.uk",
        customer_phone="01273 987654", customer_company="Coastal Property Claims",
        location_address="Marine Parade, Brighton BN2 1TL",
        location_lat=50.8193, location_lng=-0.1244, urgency="urgent",
        job_description="Storm damage assessment for seafront properties. Insurance assessors require aerial imagery of roof and structural damage from recent storm.",
        footage_purpose="insurance", heard_about="referral",
    ),
    # 3 — assigned, real_estate, Bath Georgian townhouse
    dict(
        status="assigned", job_type="real_estate", pilot_key="schen",
        customer_name="Marchmont Estates", customer_email="lettings@marchmont.co.uk",
        customer_phone="01225 334455", customer_company="Marchmont Estates",
        location_address="Royal Crescent, Bath BA1 2LR",
        location_lat=51.3880, location_lng=-2.3685, urgency="normal",
        job_description="Premium aerial photography of Georgian townhouse for high-end property listing. Requires golden-hour shots showing the full crescent.",
        footage_purpose="real_estate_listing", heard_about="website",
    ),
    # 4 — assigned, aerial_photo, Edinburgh Castle
    dict(
        status="assigned", job_type="aerial_photo", pilot_key="rcooper",
        customer_name="VisitScotland Media", customer_email="media@visitscotland.com",
        customer_phone="0131 472 2222", customer_company="VisitScotland",
        location_address="Edinburgh Castle, Castlehill, Edinburgh EH1 2NG",
        location_lat=55.9486, location_lng=-3.1999, urgency="normal",
        job_description="Scenic aerial photography of Edinburgh Castle and surroundings for tourism campaign. Multiple angles required at dawn.",
        footage_purpose="marketing", heard_about="referral",
    ),
    # 5 — accepted, agriculture, Norfolk crop field (gets waypoints)
    dict(
        status="accepted", job_type="agriculture", pilot_key="jmitchell",
        customer_name="Norfolk Grain Co-op", customer_email="farm@norfolkgrain.co.uk",
        customer_phone="01603 778899", customer_company="Norfolk Grain Co-op",
        location_address="Field off B1145, Heydon, Norfolk NR11 6RE",
        location_lat=52.8225, location_lng=1.1780, urgency="normal",
        job_description="NDVI crop health survey across 80-hectare wheat field. Farmer suspects variable nitrogen uptake in eastern sections.",
        footage_purpose="progress_report", heard_about="social_media",
        scheduled_date=_date_future(days=7),
    ),
    # 6 — accepted, event_celebration, Lake District wedding
    dict(
        status="accepted", job_type="event_celebration", pilot_key="rcooper",
        customer_name="Laura & Tom Henderson", customer_email="laura.henderson@email.co.uk",
        customer_phone="07812 345678",
        location_address="Armathwaite Hall, Bassenthwaite Lake, Keswick CA12 4RE",
        location_lat=54.6700, location_lng=-3.2150, urgency="normal",
        job_description="Wedding day aerial coverage. Ceremony at 2pm lakeside. Need arrival shots, venue overview, and couple portraits from air.",
        footage_purpose="personal", heard_about="social_media",
        scheduled_date=_date_future(days=14),
    ),
    # 7 — in_progress, inspection, Bristol Clifton Bridge (gets waypoints + risk)
    dict(
        status="in_progress", job_type="inspection", pilot_key="dokonkwo",
        customer_name="Bristol City Council", customer_email="infrastructure@bristol.gov.uk",
        customer_phone="0117 922 2000", customer_company="Bristol City Council",
        location_address="Clifton Suspension Bridge, Bridge Rd, Bristol BS8 3PA",
        location_lat=51.4545, location_lng=-2.6277, urgency="high",
        job_description="Bi-annual structural inspection of bridge supports and suspension cables. Close-range imagery required for engineering assessment.",
        footage_purpose="progress_report", heard_about="referral",
        scheduled_date=_date_ago(days=0).date() if isinstance(_date_ago(days=0), datetime) else _date_ago(days=0),
    ),
    # 8 — in_progress, construction, London Canary Wharf (gets waypoints + risk)
    dict(
        status="in_progress", job_type="construction", pilot_key="jmitchell",
        customer_name="Apex Developments", customer_email="pm@apexdev.co.uk",
        customer_phone="020 7001 2345", customer_company="Apex Developments Plc",
        location_address="Wood Wharf, Canary Wharf, London E14 9SF",
        location_lat=51.5055, location_lng=-0.0157, urgency="high",
        job_description="Weekly construction progress monitoring of 42-storey residential tower. Requires full facade capture and comparison with BIM model.",
        footage_purpose="progress_report", heard_about="google",
        scheduled_date=_date_ago(days=0).date() if isinstance(_date_ago(days=0), datetime) else _date_ago(days=0),
    ),
    # 9 — flight_complete, survey, Cornwall coastal cliffs (gets waypoints + risk)
    dict(
        status="flight_complete", job_type="survey", pilot_key="jmitchell",
        customer_name="Cornwall Heritage Trust", customer_email="surveys@cornwallheritage.org.uk",
        customer_phone="01872 241100", customer_company="Cornwall Heritage Trust",
        location_address="Bedruthan Steps, Mawgan Porth, Cornwall TR8 4BU",
        location_lat=50.4812, location_lng=-5.0215, urgency="normal",
        job_description="Coastal erosion mapping survey. 2km stretch of cliff face requires high-resolution photogrammetry for 3D model generation.",
        footage_purpose="progress_report", heard_about="website",
    ),
    # 10 — delivered, real_estate, Oxford manor house (risk)
    dict(
        status="delivered", job_type="real_estate", pilot_key="schen",
        customer_name="Savills Oxford", customer_email="oxford@savills.co.uk",
        customer_phone="01865 339700", customer_company="Savills",
        location_address="Blenheim Lodge, Woodstock Road, Oxford OX2 6GG",
        location_lat=51.7769, location_lng=-1.2983, urgency="normal",
        job_description="Luxury manor house aerial photography for property listing. Requires dawn and dusk captures showing grounds and surrounding countryside.",
        footage_purpose="real_estate_listing", heard_about="referral",
    ),
    # 11 — closed, survey, Cardiff solar farm (gets waypoints + risk, full trail)
    dict(
        status="closed", job_type="survey", pilot_key="jmitchell",
        customer_name="Welsh Power Generation", customer_email="ops@welshpower.co.uk",
        customer_phone="029 2087 4321", customer_company="Welsh Power Generation Ltd",
        location_address="Wentloog Solar Farm, Rumney, Cardiff CF3 2EE",
        location_lat=51.4830, location_lng=-3.1180, urgency="normal",
        job_description="Annual solar panel inspection survey. Thermal imaging of 12,000 panels to identify hotspots and degradation.",
        footage_purpose="progress_report", heard_about="google",
    ),
    # 12 — closed, aerial_photo, London Tower Bridge (gets waypoints + risk)
    dict(
        status="closed", job_type="aerial_photo", pilot_key="schen",
        customer_name="Thames Media Productions", customer_email="shoots@thamesmedia.co.uk",
        customer_phone="020 7403 5566", customer_company="Thames Media Productions",
        location_address="Tower Bridge, London SE1 2UP",
        location_lat=51.5055, location_lng=-0.0754, urgency="normal",
        job_description="Cinematic aerial footage of Tower Bridge for documentary production. Dawn shoot required for optimal lighting and minimal traffic.",
        footage_purpose="marketing", heard_about="social_media",
    ),
    # 13 — declined, inspection, Manchester bridge (Emma declined → James reassigned)
    dict(
        status="declined", job_type="inspection", pilot_key="jmitchell",
        customer_name="Transport for Greater Manchester", customer_email="bridges@tfgm.com",
        customer_phone="0161 244 1000", customer_company="TfGM",
        location_address="Barton Swing Aqueduct, Eccles, Manchester M30 7PZ",
        location_lat=53.4735, location_lng=-2.3517, urgency="high",
        job_description="Emergency bridge inspection following reported cracks in masonry. Close-range imaging of all support pillars required.",
        footage_purpose="legal_evidence", heard_about="referral",
    ),
    # 14 — declined, survey, Heathrow perimeter (restricted airspace, no reassignment)
    dict(
        status="declined", job_type="survey", pilot_key=None,
        customer_name="Heathrow Environmental", customer_email="env@heathrow.co.uk",
        customer_phone="020 8757 1234", customer_company="Heathrow Airport Ltd",
        location_address="Perimeter Road, Heathrow Airport, Hounslow TW6 2GW",
        location_lat=51.4700, location_lng=-0.4543, urgency="normal",
        job_description="Environmental survey of airport perimeter grasslands. Requires drone flight within airport controlled airspace zone.",
        footage_purpose="progress_report", heard_about="website",
    ),
    # 15 — closed, agriculture, Yorkshire wind farm (gets waypoints + risk)
    dict(
        status="closed", job_type="agriculture", pilot_key="dokonkwo",
        customer_name="Yorkshire Wind Farms Ltd", customer_email="ops@yorkshirewind.co.uk",
        customer_phone="01423 505050", customer_company="Yorkshire Wind Farms Ltd",
        location_address="Knabs Ridge Wind Farm, Harrogate HG3 3BJ",
        location_lat=54.0176, location_lng=-1.6890, urgency="normal",
        job_description="Turbine blade inspection survey across 22 turbines. Close-range photography of leading edges for erosion assessment.",
        footage_purpose="progress_report", heard_about="google",
    ),
]


def _create_flight_plan(spec, ref_idx):
    """Create a FlightPlan from an order spec dict."""
    fp = FlightPlan(
        reference=f"FP-20260316-{1000 + ref_idx}",
        status="completed" if spec["status"] in ("flight_complete", "delivered", "closed") else "route_planned",
        customer_name=spec["customer_name"],
        customer_email=spec["customer_email"],
        customer_phone=spec.get("customer_phone"),
        customer_company=spec.get("customer_company"),
        job_type=spec["job_type"],
        job_description=spec.get("job_description"),
        urgency=spec.get("urgency", "normal"),
        location_address=spec["location_address"],
        location_lat=spec["location_lat"],
        location_lng=spec["location_lng"],
        footage_purpose=spec.get("footage_purpose"),
        heard_about=spec.get("heard_about"),
        consent_given=True,
        created_at=_ago(days=30 - ref_idx),
    )
    return fp


def _create_orders(users):
    """Create all 15 orders with flight plans, returning list of (order, spec) tuples."""
    print("  Creating flight plans and orders...")
    admin = users["admin"]
    results = []

    for idx, spec in enumerate(ORDER_SPECS):
        fp = _create_flight_plan(spec, idx)
        db.session.add(fp)
        db.session.flush()

        pilot = users.get(spec["pilot_key"]) if spec["pilot_key"] else None

        order = Order(
            flight_plan_id=fp.id,
            pilot_id=pilot.id if pilot else None,
            assigned_by_id=admin.id if pilot else None,
            status=spec["status"],
            scheduled_date=spec.get("scheduled_date"),
            created_at=fp.created_at,
        )

        # Set timestamps based on status progression
        base = fp.created_at
        if spec["status"] in ("assigned", "accepted", "in_progress", "flight_complete", "delivered", "closed", "declined"):
            order.assigned_at = base + timedelta(hours=2)
        if spec["status"] in ("accepted", "in_progress", "flight_complete", "delivered", "closed"):
            order.accepted_at = base + timedelta(hours=6)
        if spec["status"] in ("in_progress", "flight_complete", "delivered", "closed"):
            order.started_at = base + timedelta(days=1)
        if spec["status"] in ("flight_complete", "delivered", "closed"):
            order.completed_at = base + timedelta(days=1, hours=4)
        if spec["status"] in ("delivered", "closed"):
            order.delivered_at = base + timedelta(days=2)
        if spec["status"] == "closed":
            order.closed_at = base + timedelta(days=3)

        # Special case: order 13 (declined) and 14 (declined)
        if idx == 12:  # order 13 — Emma declined, reassigned to James
            order.assignment_notes = "Reassigned to James after Emma declined due to severe weather."
            order.decline_reason = "Severe weather conditions — 45 km/h sustained wind, heavy rain, visibility under 2km. Unsafe to fly."
        if idx == 13:  # order 14 — restricted airspace
            order.pilot_id = None
            order.decline_reason = "Flight location is within Heathrow CTR restricted airspace. NOTAM check confirms no drone operations permitted without special CAA clearance which has not been obtained."

        # Notes for completed/delivered orders
        if spec["status"] in ("flight_complete", "delivered", "closed"):
            order.completion_notes = "Flight completed successfully. All planned waypoints covered."
        if spec["status"] == "delivered":
            order.pilot_notes = "Excellent conditions. All footage captured as briefed."

        db.session.add(order)
        db.session.flush()
        results.append((order, spec, fp))

    db.session.commit()
    return results


# ---------------------------------------------------------------------------
# 5. WAYPOINTS & POIs
# ---------------------------------------------------------------------------

WAYPOINT_SETS = {
    # order index: list of (lat, lng, altitude, action_type)
    4: [  # Norfolk crop field
        (52.8225, 1.1780, 50, None), (52.8235, 1.1800, 50, None),
        (52.8245, 1.1820, 50, "take_photo"), (52.8255, 1.1800, 50, None),
        (52.8265, 1.1780, 50, "take_photo"), (52.8255, 1.1760, 50, None),
        (52.8245, 1.1740, 50, None), (52.8235, 1.1760, 50, "take_photo"),
    ],
    6: [  # Clifton Bridge
        (51.4545, -2.6277, 40, None), (51.4548, -2.6270, 35, "take_photo"),
        (51.4550, -2.6265, 30, "take_photo"), (51.4547, -2.6260, 35, None),
        (51.4543, -2.6255, 40, "take_photo"), (51.4540, -2.6260, 45, None),
    ],
    7: [  # Canary Wharf
        (51.5055, -0.0157, 80, None), (51.5058, -0.0150, 85, "take_photo"),
        (51.5060, -0.0145, 90, "take_photo"), (51.5058, -0.0140, 85, None),
        (51.5055, -0.0135, 80, "take_photo"), (51.5052, -0.0140, 75, None),
        (51.5050, -0.0150, 80, "take_photo"),
    ],
    8: [  # Cornwall cliffs
        (50.4812, -5.0215, 60, None), (50.4815, -5.0200, 55, "take_photo"),
        (50.4818, -5.0185, 50, "take_photo"), (50.4820, -5.0170, 55, None),
        (50.4818, -5.0155, 60, "take_photo"), (50.4815, -5.0140, 55, None),
    ],
    10: [  # Cardiff solar farm
        (51.4830, -3.1180, 45, None), (51.4835, -3.1170, 45, "take_photo"),
        (51.4840, -3.1160, 45, "take_photo"), (51.4845, -3.1150, 45, None),
        (51.4840, -3.1140, 45, "take_photo"), (51.4835, -3.1150, 45, None),
        (51.4830, -3.1160, 45, None),
    ],
    11: [  # Tower Bridge
        (51.5055, -0.0754, 50, None), (51.5058, -0.0748, 45, "take_photo"),
        (51.5060, -0.0742, 40, "take_photo"), (51.5058, -0.0736, 45, None),
        (51.5055, -0.0730, 50, "take_photo"),
    ],
    14: [  # Yorkshire wind farm
        (54.0176, -1.6890, 70, None), (54.0180, -1.6880, 75, "take_photo"),
        (54.0184, -1.6870, 80, "take_photo"), (54.0188, -1.6860, 75, None),
        (54.0184, -1.6850, 70, "take_photo"), (54.0180, -1.6860, 75, None),
        (54.0176, -1.6870, 70, "take_photo"), (54.0172, -1.6880, 70, None),
    ],
}

POI_SETS = {
    # inspection/survey jobs get POIs
    6: [  # Clifton Bridge
        (51.4546, -2.6270, "North tower base"),
        (51.4544, -2.6260, "South tower base"),
        (51.4548, -2.6265, "Main cable mid-span"),
    ],
    8: [  # Cornwall cliffs
        (50.4816, -5.0200, "Cliff face section A"),
        (50.4819, -5.0175, "Erosion hotspot"),
    ],
    10: [  # Cardiff solar farm
        (51.4835, -3.1165, "Panel array block A"),
        (51.4842, -3.1155, "Inverter station"),
        (51.4838, -3.1145, "Panel array block B"),
    ],
    14: [  # Yorkshire wind farm
        (54.0180, -1.6875, "Turbine T1 — blade damage"),
        (54.0186, -1.6858, "Turbine T5 — leading edge erosion"),
    ],
}


def _create_waypoints_and_pois(order_results):
    print("  Creating waypoints and POIs...")
    for idx, (order, spec, fp) in enumerate(order_results):
        if idx in WAYPOINT_SETS:
            for wi, (lat, lng, alt, action) in enumerate(WAYPOINT_SETS[idx]):
                db.session.add(Waypoint(
                    flight_plan_id=fp.id, index=wi,
                    lat=lat, lng=lng, altitude_m=alt,
                    action_type=action,
                ))
        if idx in POI_SETS:
            for pi, (lat, lng, label) in enumerate(POI_SETS[idx]):
                db.session.add(POI(
                    flight_plan_id=fp.id, lat=lat, lng=lng,
                    label=label, sort_order=pi,
                ))
    db.session.commit()


# ---------------------------------------------------------------------------
# 6. RISK ASSESSMENTS
# ---------------------------------------------------------------------------

def _all_checks_true():
    """Return dict with all 28 check fields set to True."""
    return {f: True for f in RiskAssessment.CHECK_FIELDS}


def _create_risk_assessments(order_results, users):
    print("  Creating risk assessments...")

    # Orders that get risk assessments: 7,8,9,10,11,12,13(Emma's abort),15
    risk_configs = {
        6: dict(  # order 7 — Clifton Bridge, proceed_with_mitigations
            pilot_key="dokonkwo", risk_level="medium", decision="proceed_with_mitigations",
            mitigation_notes="Moderate wind 20 km/h gusting 28 km/h. Operating within limits but maintaining extra distance from bridge structure. Reduced altitude ceiling to 40m.",
            weather_wind_speed=20.0, weather_wind_direction="SW", weather_visibility=8.0,
            weather_precipitation="None", weather_temperature=14.0,
            equip_battery_level=95, airspace_planned_altitude=40.0,
        ),
        7: dict(  # order 8 — Canary Wharf, proceed_with_mitigations
            pilot_key="jmitchell", risk_level="medium", decision="proceed_with_mitigations",
            mitigation_notes="Wind funnelling between buildings causing gusts up to 30 km/h at altitude. Using DJI Mavic 3 for stability. Operating in Sport mode for wind resistance. Extra spotter deployed.",
            weather_wind_speed=22.0, weather_wind_direction="W", weather_visibility=10.0,
            weather_precipitation="None", weather_temperature=12.0,
            equip_battery_level=100, airspace_planned_altitude=90.0,
        ),
        8: dict(  # order 9 — Cornwall, proceed
            pilot_key="jmitchell", risk_level="low", decision="proceed",
            mitigation_notes=None,
            weather_wind_speed=10.0, weather_wind_direction="N", weather_visibility=15.0,
            weather_precipitation="None", weather_temperature=16.0,
            equip_battery_level=98, airspace_planned_altitude=60.0,
        ),
        9: dict(  # order 10 — Oxford, proceed
            pilot_key="schen", risk_level="low", decision="proceed",
            mitigation_notes=None,
            weather_wind_speed=8.0, weather_wind_direction="NE", weather_visibility=20.0,
            weather_precipitation="None", weather_temperature=18.0,
            equip_battery_level=100, airspace_planned_altitude=50.0,
        ),
        10: dict(  # order 11 — Cardiff, proceed
            pilot_key="jmitchell", risk_level="low", decision="proceed",
            mitigation_notes=None,
            weather_wind_speed=12.0, weather_wind_direction="SE", weather_visibility=12.0,
            weather_precipitation="None", weather_temperature=15.0,
            equip_battery_level=96, airspace_planned_altitude=45.0,
        ),
        11: dict(  # order 12 — Tower Bridge, proceed
            pilot_key="schen", risk_level="low", decision="proceed",
            mitigation_notes=None,
            weather_wind_speed=6.0, weather_wind_direction="E", weather_visibility=25.0,
            weather_precipitation="None", weather_temperature=11.0,
            equip_battery_level=100, airspace_planned_altitude=50.0,
        ),
        14: dict(  # order 15 — Yorkshire, proceed
            pilot_key="dokonkwo", risk_level="low", decision="proceed",
            mitigation_notes=None,
            weather_wind_speed=15.0, weather_wind_direction="NW", weather_visibility=18.0,
            weather_precipitation="None", weather_temperature=9.0,
            equip_battery_level=94, airspace_planned_altitude=75.0,
        ),
    }

    for idx, (order, spec, fp) in enumerate(order_results):
        if idx not in risk_configs:
            continue

        rc = risk_configs[idx]
        pilot = users[rc["pilot_key"]]
        checks = _all_checks_true()

        ra = RiskAssessment(
            order_id=order.id,
            pilot_id=pilot.id,
            risk_level=rc["risk_level"],
            decision=rc["decision"],
            mitigation_notes=rc.get("mitigation_notes"),
            pilot_declaration=True,
            gps_latitude=spec["location_lat"],
            gps_longitude=spec["location_lng"],
            weather_wind_speed=rc["weather_wind_speed"],
            weather_wind_direction=rc["weather_wind_direction"],
            weather_visibility=rc["weather_visibility"],
            weather_precipitation=rc["weather_precipitation"],
            weather_temperature=rc["weather_temperature"],
            equip_battery_level=rc["equip_battery_level"],
            airspace_planned_altitude=rc["airspace_planned_altitude"],
            created_at=order.started_at or order.accepted_at or order.assigned_at or order.created_at,
            **checks,
        )
        db.session.add(ra)

        # Mark risk assessment completed on the order
        order.risk_assessment_completed = True

    # Special: Order 13 (index 12) — Emma's abort risk assessment
    order_13, spec_13, fp_13 = order_results[12]
    emma = users["ewhitfield"]
    abort_checks = _all_checks_true()
    # Weather check fails for abort
    abort_checks["weather_acceptable"] = False

    ra_abort = RiskAssessment(
        order_id=order_13.id,
        pilot_id=emma.id,
        risk_level="high",
        decision="abort",
        mitigation_notes="ABORT: Severe weather conditions. Sustained wind 45 km/h with gusts to 60 km/h. Visibility 2km in heavy rain. Conditions exceed all safe operating limits. Flight cancelled — recommend rescheduling.",
        pilot_declaration=True,
        gps_latitude=spec_13["location_lat"],
        gps_longitude=spec_13["location_lng"],
        weather_wind_speed=45.0,
        weather_wind_direction="NW",
        weather_visibility=2.0,
        weather_precipitation="Heavy rain",
        weather_temperature=6.0,
        equip_battery_level=100,
        airspace_planned_altitude=40.0,
        created_at=order_13.assigned_at + timedelta(hours=2) if order_13.assigned_at else order_13.created_at,
        **abort_checks,
    )
    db.session.add(ra_abort)
    order_13.risk_assessment_completed = True

    db.session.commit()


# ---------------------------------------------------------------------------
# 7. ACTIVITY LOGS
# ---------------------------------------------------------------------------

def _log(order, user, action, old_value=None, new_value=None, details=None, ts=None):
    return OrderActivity(
        order_id=order.id,
        user_id=user.id if user else None,
        action=action,
        old_value=old_value,
        new_value=new_value,
        details=details,
        created_at=ts or _now(),
    )


def _create_activity_logs(order_results, users):
    print("  Creating activity logs...")
    admin = users["admin"]

    for idx, (order, spec, fp) in enumerate(order_results):
        base = order.created_at
        pilot = users.get(spec["pilot_key"])

        # Every order starts with "created"
        db.session.add(_log(order, admin, "created",
            details=f"Flight plan {fp.reference} submitted by {spec['customer_name']}.",
            ts=base))

        if spec["status"] == "pending_assignment":
            continue  # just created, nothing else

        # Assigned
        if pilot and spec["status"] != "declined" or idx == 12:
            assign_pilot = users["ewhitfield"] if idx == 12 else pilot
            db.session.add(_log(order, admin, "assigned",
                new_value=assign_pilot.display_name,
                details=f"Order assigned to {assign_pilot.display_name} by Admin.",
                ts=base + timedelta(hours=2)))

        if spec["status"] == "assigned":
            continue

        # For order 13: Emma declines, then James is assigned and accepts
        if idx == 12:
            emma = users["ewhitfield"]
            james = users["jmitchell"]
            db.session.add(_log(order, emma, "declined",
                details="Declined due to severe weather — 45 km/h sustained wind, heavy rain, visibility under 2km.",
                ts=base + timedelta(hours=4)))
            db.session.add(_log(order, emma, "risk_assessment_completed",
                details="Risk assessment completed: ABORT — severe weather conditions.",
                ts=base + timedelta(hours=4, minutes=5)))
            db.session.add(_log(order, admin, "assigned",
                new_value=james.display_name,
                details=f"Reassigned to {james.display_name} after weather decline.",
                ts=base + timedelta(hours=6)))
            db.session.add(_log(order, james, "accepted",
                details=f"{james.display_name} accepted the reassigned order.",
                ts=base + timedelta(hours=8)))
            continue

        # For order 14: declined for airspace, no pilot
        if idx == 13:
            db.session.add(_log(order, admin, "assigned",
                new_value="Pending review",
                details="Order flagged for airspace review — Heathrow CTR.",
                ts=base + timedelta(hours=1)))
            db.session.add(_log(order, admin, "declined",
                details="Declined — restricted airspace within Heathrow CTR. CAA special clearance not obtained.",
                ts=base + timedelta(hours=3)))
            continue

        # Accepted
        if spec["status"] in ("accepted", "in_progress", "flight_complete", "delivered", "closed"):
            db.session.add(_log(order, pilot, "accepted",
                details=f"{pilot.display_name} accepted the order.",
                ts=base + timedelta(hours=6)))

        if spec["status"] == "accepted":
            continue

        # In progress — risk assessment + status change
        if spec["status"] in ("in_progress", "flight_complete", "delivered", "closed"):
            db.session.add(_log(order, pilot, "risk_assessment_completed",
                details="Pre-flight risk assessment completed.",
                ts=base + timedelta(days=1, minutes=-30)))
            db.session.add(_log(order, pilot, "status_changed",
                old_value="accepted", new_value="in_progress",
                details=f"{pilot.display_name} started the flight.",
                ts=base + timedelta(days=1)))

        if spec["status"] == "in_progress":
            continue

        # Flight complete
        if spec["status"] in ("flight_complete", "delivered", "closed"):
            db.session.add(_log(order, pilot, "status_changed",
                old_value="in_progress", new_value="flight_complete",
                details="Flight completed. All waypoints covered successfully.",
                ts=base + timedelta(days=1, hours=4)))

        if spec["status"] == "flight_complete":
            continue

        # Delivered
        if spec["status"] in ("delivered", "closed"):
            db.session.add(_log(order, pilot, "deliverable_uploaded",
                details="Processed imagery and reports uploaded.",
                ts=base + timedelta(days=2, hours=-2)))
            db.session.add(_log(order, pilot, "status_changed",
                old_value="flight_complete", new_value="delivered",
                details="Deliverables sent to customer.",
                ts=base + timedelta(days=2)))

        if spec["status"] == "delivered":
            continue

        # Closed
        if spec["status"] == "closed":
            db.session.add(_log(order, admin, "status_changed",
                old_value="delivered", new_value="closed",
                details="Order closed. Customer confirmed receipt of deliverables.",
                ts=base + timedelta(days=3)))

    db.session.commit()


# ---------------------------------------------------------------------------
# MAIN
# ---------------------------------------------------------------------------

def seed_demo_data():
    """Delete all data and create comprehensive demo dataset."""
    print("Seeding demo data...")

    _wipe_all()
    _seed_lookups()
    users = _create_users()
    order_results = _create_orders(users)
    _create_waypoints_and_pois(order_results)
    _create_risk_assessments(order_results, users)
    _create_activity_logs(order_results, users)

    print("Done! Created:")
    print(f"  - {User.query.count()} users (1 admin + 5 pilots)")
    print(f"  - {FlightPlan.query.count()} flight plans")
    print(f"  - {Order.query.count()} orders")
    print(f"  - {RiskAssessment.query.count()} risk assessments")
    print(f"  - {Waypoint.query.count()} waypoints")
    print(f"  - {POI.query.count()} POIs")
    print(f"  - {OrderActivity.query.count()} activity log entries")
