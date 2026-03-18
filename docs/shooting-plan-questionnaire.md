# Videography Shooting Plan Questionnaire

> **Purpose:** Structured intake for drone videography jobs. Captures the creative brief from
> the customer and the technical flight-planning detail filled in by the admin/pilot.
>
> **How to use:** Part A is customer-facing (public intake form). Part B is admin-only
> (detail page planning). Part C provides reference tables for translating creative intent
> into mission parameters.

---

## PART A — Customer Brief

*Filled by the customer via the public intake form.*

---

### A1. Project Type & Platform

**Question:** What kind of video project is this, and where will it be published?

| Option | Example |
|--------|---------|
| Real Estate Listing | Zillow / Rightmove walkthrough |
| Brand Film / Commercial | Company website hero video |
| Event Coverage | Wedding, festival, sports |
| Social Media Content | Instagram Reels, TikTok, YouTube Shorts |
| Music Video | Artist performance or narrative clip |
| Documentary / Journalism | Aerial b-roll for long-form piece |
| Construction Progress | Monthly site update for stakeholders |
| Tourism / Destination | Hotel or location showcase |
| Personal / Keepsake | Family event, travel memory |
| Other (free text) | — |

**Why it matters:** Project type drives default shot selection, pacing, and output format.
A real-estate walkthrough needs smooth reveals and wide establishing shots; a social-media
reel needs punchy, fast-cut movements. Knowing the platform also determines aspect ratio
and duration defaults.

> **Existing field overlap:** `JobType` model covers some categories (real_estate,
> event_celebration, construction, etc.) and `PurposeOption` captures footage purpose
> (marketing, social_media, real_estate_listing, etc.). This question merges both into
> a single customer-friendly prompt.

---

### A2. Target Aspect Ratio & Duration

**Question:** What aspect ratio and approximate duration do you need?

**Aspect ratio options:**

| Option | Use case |
|--------|----------|
| 16:9 (landscape) | YouTube, TV, website hero |
| 9:16 (portrait) | Instagram Reels, TikTok, YouTube Shorts |
| 1:1 (square) | Instagram feed, Facebook |
| 2.39:1 (cinematic widescreen) | Film, high-end brand content |
| 4:3 (classic) | Legacy broadcast or stylistic choice |
| Multiple / undecided | Deliver in several formats |

**Duration options:**

| Option | Typical use |
|--------|-------------|
| < 30 seconds | Social media clip, teaser |
| 30–60 seconds | Reel, short promo |
| 1–3 minutes | Standard showcase / walkthrough |
| 3–5 minutes | Detailed tour, mini-documentary |
| 5+ minutes | Full documentary segment, event recap |
| Raw footage only | Client handles editing |

**Why it matters:** Aspect ratio determines whether we need to fly at different altitudes
or reframe shots (portrait crops require more vertical headroom). Duration tells us how
many distinct shots to capture — a 30-second reel might need 6–8 clips of 5 seconds each,
while a 3-minute film needs 20+ varied shots and multiple battery swaps.

> **Existing field overlap:** `video_resolution` (4k / 1080p) and `output_format`
> (raw, edited_video, photos_video, etc.) partially cover this. Aspect ratio and
> duration are new.

---

### A3. Subject Description

**Question:** Describe the main subject. What is it, roughly how big is it, and does it move?

**Sub-questions:**

| Field | Options / free text |
|-------|---------------------|
| Subject type | Building, vehicle, person/group, landscape, event area, construction site, other |
| Approximate size | Small (< 5 m), Medium (5–30 m), Large (30–100 m), Very large (> 100 m) |
| Movement | Static, Slow-moving (walking pace), Fast-moving (vehicle/athlete), Unpredictable |
| Elevation | Ground level, Elevated (rooftop, hill), Multi-level (tall building) |

**Why it matters:** Subject size directly determines orbit radius, standoff distance, and
altitude. A 5 m statue needs a 15 m orbit; a 100 m building needs a 60 m+ orbit. Movement
determines whether we use tracking/follow patterns or static orbits, and whether the pilot
needs sport mode for faster subjects.

---

### A4. Key Features to Highlight

**Question:** List the 3–5 most important features, angles, or areas you want captured.

*Free text with examples:*
- "The rooftop terrace with city views"
- "The car approaching from the east entrance"
- "The waterfall from both above and eye-level"
- "The crowd energy during the ceremony"

**Why it matters:** This drives specific waypoint placement and gimbal angles. Each
highlighted feature becomes a planned shot with intentional framing rather than a generic
flyover.

---

### A5. Desired Shot Types

**Question:** Select all shot types you'd like included in your video.

#### Establishing & Wide Shots

| Shot | Description |
|------|-------------|
| **High-altitude reveal** | Start high, tilt down to reveal the subject from above |
| **Low-altitude flyover** | Skim over the landscape at 5–15 m for an immersive feel |
| **Pull-back reveal** | Start close on a detail, pull back to show the full scene |
| **Top-down / bird's eye** | Camera points straight down, drone moves laterally |
| **Panoramic sweep** | Slow yaw rotation capturing a wide landscape or skyline |

#### Orbiting & Circling

| Shot | Description |
|------|-------------|
| **Full orbit** | Complete 360° circle around the subject |
| **Partial orbit (arc)** | 90°–180° arc, ideal for showing a building's best side |
| **Ascending orbit (spiral)** | Circle while climbing — dramatic reveal effect |
| **Descending orbit (spiral)** | Circle while descending — closing / landing sequence |
| **Multi-altitude orbit** | Stacked orbits at different heights for 3D feel |

#### Linear & Tracking

| Shot | Description |
|------|-------------|
| **Cable cam (A-to-B)** | Smooth straight-line fly between two points |
| **Tracking / follow** | Drone follows a moving subject from behind or beside |
| **Lead shot** | Drone flies ahead of a moving subject, facing back |
| **Lateral dolly** | Side-to-side movement parallel to the subject |
| **Fly-through** | Pass between or through structures (advanced) |

#### Detail & Inspection

| Shot | Description |
|------|-------------|
| **Close-up detail** | Tight framing on a specific feature or texture |
| **Facade scan** | Systematic vertical pass along a building face |
| **Roof / top inspection** | Nadir or low-angle pass over a rooftop |

#### Creative & Cinematic

| Shot | Description |
|------|-------------|
| **Dronie (selfie pull-back)** | Start on a person, fly up and away |
| **Hyperlapse** | Time-compressed flight path, smoothed in post |
| **Parallax slide** | Lateral movement revealing depth between foreground and background |

**Why it matters:** Each shot type maps to a specific mission pattern and waypoint
configuration. Selecting shots upfront lets the admin pre-plan patterns, estimate battery
usage, and sequence the flight efficiently. See Part C, Table 1 for the full mapping.

> **Existing field overlap:** `flight_plan.shot_types` currently offers only 4 options:
> overview, close_up, orbit, tracking. This expanded list (20 options) replaces those
> with video-native vocabulary.

---

### A6. Shot Priorities (Top 3)

**Question:** Of the shots you selected, which 3 are absolute must-haves?

1. ____________________
2. ____________________
3. ____________________

**Why it matters:** Battery life is finite. If weather or battery limits force us to cut
shots, we need to know which ones matter most. These get planned first and flown on
fresh batteries.

---

### A7. Mood & Visual Style

**Question:** What overall mood or feeling should the video convey?

| Option | Characteristics |
|--------|-----------------|
| Epic / Grand | High altitude, slow movements, sweeping reveals |
| Calm / Peaceful | Low speed, gentle arcs, soft transitions |
| Energetic / Dynamic | Fast movements, rapid altitude changes, sport mode |
| Intimate / Personal | Low altitude, close framing, tracking shots |
| Dramatic / Cinematic | Strong contrasts, deliberate pacing, widescreen |
| Documentary / Neutral | Steady, informational, minimal stylization |
| Playful / Fun | Mixed speeds, creative angles, unexpected perspectives |

**Why it matters:** Mood maps directly to speed_ms defaults, altitude choices, and turn
smoothing. "Epic" = slow 3 m/s orbits with smooth curves. "Energetic" = 8+ m/s passes
with sharp turns.

---

### A8. Speed & Pacing Preference

**Question:** How fast should the drone movements feel?

| Option | Feel | Typical speed | Best for |
|--------|------|---------------|----------|
| Very slow | Meditative, dreamy | 1–2 m/s | Luxury real estate, spa, wellness |
| Slow | Smooth, elegant | 2–4 m/s | Most real estate, brand films |
| Medium | Natural, balanced | 4–6 m/s | Events, tourism, general |
| Fast | Energetic, exciting | 6–10 m/s | Sports, action, music videos |
| Mixed | Varies per shot | Per shot | Complex narrative edits |

**Why it matters:** Speed directly sets the `speed_ms` waypoint parameter. See Part C,
Table 2 for the full mapping.

---

### A9. Opening & Closing Shot Ideas

**Question:** Do you have a preference for how the video begins and ends?

**Opening shot options:**

| Option | Description |
|--------|-------------|
| High reveal | Start high, descend/tilt to reveal subject |
| Ground-level rise | Start near ground, ascend to full view |
| Detail to wide | Start on a close-up, pull back |
| Approach | Fly toward the subject from a distance |
| No preference | Pilot's choice |

**Closing shot options:**

| Option | Description |
|--------|-------------|
| Pull-away | Ascend and pull back from subject |
| Orbit freeze | End mid-orbit for a cinematic hold |
| Fly-over exit | Pass over the subject and continue |
| Detail close | End on a tight, meaningful detail |
| No preference | Pilot's choice |

**Why it matters:** Opening and closing shots are planned as the first and last patterns
in the mission sequence. They often need the best lighting and a full battery.

---

### A10. Reference Videos & Inspiration

**Question:** Share 1–3 links to videos whose style or specific shots you'd like to
replicate (YouTube, Vimeo, Instagram, etc.).

- URL 1: ____________________  Timestamp / note: ____________
- URL 2: ____________________  Timestamp / note: ____________
- URL 3: ____________________  Timestamp / note: ____________

**Why it matters:** Reference videos remove ambiguity. The admin can match specific shots
to mission patterns and estimate the flight complexity before the shoot day.

---

### A11. Time of Day & Lighting Preference

**Question:** When should the shoot happen for best results?

| Option | Lighting quality |
|--------|-----------------|
| Sunrise / Golden hour (morning) | Warm, soft, long shadows — cinematic |
| Mid-morning | Bright, even — good for real estate interiors + exteriors |
| Midday | Harsh, flat — generally avoided for creative work |
| Afternoon | Strong directional light — good contrast |
| Sunset / Golden hour (evening) | Warm, dramatic — most popular for cinematic |
| Blue hour (post-sunset) | Cool, moody — requires capable sensor |
| Night | Requires specialized drone / settings — limited options |
| Any / flexible | Pilot decides based on weather |

**Why it matters:** Lighting affects camera settings, drone selection (larger sensor for
low light), and scheduling. Golden hour windows are short (~30 min), which limits the
number of patterns per session.

> **Existing field overlap:** `order.time_of_day` (day / night / twilight) covers this
> coarsely. This question provides finer granularity for videography scheduling.

---

### A12. Audio Considerations

**Question:** Will audio be important for this project?

| Option | Implication |
|--------|-------------|
| Music only (added in post) | No on-site audio constraints — fly freely |
| Ambient sound needed | Minimize drone noise in certain shots, plan hover-free windows |
| Voiceover / narration | Drone noise irrelevant — voiceover recorded separately |
| Live event audio | Coordinate with sound crew, fly higher during speeches/music |
| Not applicable | — |

**Why it matters:** If ambient audio is needed, the pilot must plan quieter flight
segments (higher altitude = less audible noise) or pause the drone during audio-critical
moments. This affects pattern sequencing.

---

### A13. Privacy & Restricted Areas

**Question:** Are there any areas, people, or properties that must NOT appear in the footage?

*Free text with prompts:*
- Neighboring properties with privacy concerns
- Windows / balconies to avoid
- Restricted airspace or no-fly zones nearby
- Identifiable people who haven't consented
- Sensitive infrastructure

**Why it matters:** Privacy constraints become no-fly polygons or heading restrictions
in the mission plan. The pilot needs these mapped before flight to avoid re-shoots or
legal issues.

> **Existing field overlap:** `order.proximity_to_people`, `order.airspace_type`,
> `order.environment_type` handle regulatory aspects. This question captures
> site-specific customer knowledge that those fields don't cover.

---

### A14. Output Format & Deliverables

**Question:** What do you need delivered?

| Option | Description |
|--------|-------------|
| Raw footage only | Unedited clips, original resolution |
| Edited highlight video | Cut, color-graded, music-synced final video |
| Raw + edited | Both deliverables |
| Photos + video | Still frames extracted or separate photo pass |
| Social media package | Multiple cuts optimized for different platforms |
| Live stream | Real-time broadcast during event |

**Why it matters:** If the customer needs both photos and video, the admin plans a
separate photo pass (different camera settings, potentially different pattern).
Social media packages require planning for multiple aspect ratios. Live stream
needs real-time uplink considerations.

> **Existing field overlap:** `output_format` (raw, edited_video, photos_only,
> photos_video, livestream) covers this. This question adds the social media
> package and combined options.

---

## PART B — Admin Flight Planning

*Filled by the admin/pilot on the order detail page. Not visible to customers.*

---

### B1. Resolution & Frame Rate Selection

**Question:** What resolution and frame rate will be used?

**Resolution:**

| Option | When to use |
|--------|-------------|
| 4K (3840x2160) | Default for all professional work |
| 1080p (1920x1080) | When higher frame rates are needed (slow-mo) or storage is limited |

**Frame rate:**

| Option | When to use |
|--------|-------------|
| 24 fps | Cinematic standard — film look, natural motion blur |
| 25 fps | PAL regions (Europe, UK, Australia) broadcast standard |
| 30 fps | NTSC regions (Americas, Japan) broadcast standard |
| 48 fps | Smooth cinematic — useful for moderate slow-mo (50% at 24 fps timeline) |
| 50 fps | PAL slow-motion — 50% speed at 25 fps timeline |
| 60 fps | Smooth slow-motion — 50% speed at 30 fps, 40% at 24 fps |
| 120 fps | Heavy slow-motion (usually 1080p only) — dramatic action shots |

**Decision factors:**
- Customer's platform determines base frame rate (24 for film, 25/30 for broadcast)
- Any shot marked for slow-motion needs 2x–5x the base frame rate
- Higher frame rates require more light — affects time-of-day scheduling

> **Existing field overlap:** `video_resolution` (4k / 1080p). Frame rate is new.

---

### B2. Color Profile Decision

**Question:** What color profile / picture style should be set on the drone?

| Option | When to use |
|--------|-------------|
| Normal / Standard | Quick turnaround, minimal post-processing, customer edits themselves |
| D-Log / D-Log M | Professional color grading in post — maximum dynamic range |
| HLG (Hybrid Log-Gamma) | HDR delivery or when client has HDR-capable displays |
| D-Cinelike | Middle ground — some latitude for grading, less work than D-Log |

**Decision factors:**
- If customer selected "Raw footage only" → use Normal (they likely won't grade)
- If customer selected "Edited highlight video" → use D-Log for maximum flexibility
- Low-light conditions → D-Log preserves shadow detail
- High-contrast scenes (sunset, mixed indoor/outdoor) → D-Log or HLG

> **Existing field overlap:** None. Color profile is new.

---

### B3. Subject Scale to Orbit Radius & Standoff Distance

**Question:** Based on the subject description (A3), calculate the orbit radius and
standoff distance.

**Formula guidelines:**

| Subject size | Recommended orbit radius | Standoff distance (facade) | Min altitude |
|-------------|-------------------------|---------------------------|-------------|
| Small (< 5 m) | 10–20 m | 5–10 m | 10 m |
| Medium (5–30 m) | 20–40 m | 10–20 m | Subject height + 10 m |
| Large (30–100 m) | 40–80 m | 15–30 m | Subject height + 15 m |
| Very large (> 100 m) | 80–150 m | 20–50 m | Subject height + 20 m |

**Additional factors:**
- Moving subjects: add 50% to orbit radius for safety margin
- Obstacle density: increase standoff if trees, wires, or structures nearby
- Lens focal length: larger sensor drones (Mavic 3) can orbit wider and crop in post
- Legal minimums: maintain required distance from people and structures per local regulations

See Part C, Table 3 for full defaults.

> **Existing field overlap:** Pattern planners already use `radius_m` (orbit) and
> `standoff_m` (facade). This provides the creative-to-technical translation.

---

### B4. Shot-to-Pattern Mapping

**Question:** For each shot type the customer selected (A5), assign a mission pattern
and configure key parameters.

*Use Part C, Table 1 as the reference. Fill in the flight plan for each selected shot:*

| # | Customer shot | Pattern | radius_m | altitude_m | speed_ms | gimbal_pitch | turn_mode | Notes |
|---|--------------|---------|----------|-----------|---------|-------------|-----------|-------|
| 1 | | | | | | | | |
| 2 | | | | | | | | |
| 3 | | | | | | | | |
| … | | | | | | | | |

**Decision factors:**
- Priority shots (A6) get flown first on fresh batteries
- Group shots by pattern type to minimize reconfiguration
- Altitude progression: plan shots from highest to lowest (or vice versa) to reduce
  repositioning time
- Check that total planned shots can fit within battery endurance

---

### B5. Pattern Sequence & Battery Allocation

**Question:** Plan the order of patterns and estimate battery usage.

**Template:**

| Battery | Patterns (in order) | Est. duration | Notes |
|---------|-------------------|--------------|-------|
| Battery 1 | Opening shot, Orbit #1, Cable cam | ~8 min | Priority shots |
| Battery 2 | Multi-orbit, Tracking, Detail pass | ~10 min | Creative shots |
| Battery 3 | Photo pass (if needed), Closing shot | ~6 min | Alternate settings |

**Rules of thumb:**
- Reserve 20% battery for RTH (return to home)
- Orbit patterns: ~2 min per full orbit at 4 m/s
- Cable cam: ~1 min per 200 m segment
- Spiral: ~3 min per full ascending orbit
- Account for repositioning time between patterns (~30 sec each)

**Decision factors:**
- Priority shots from A6 go on Battery 1
- Opening shot first, closing shot last
- Group patterns that share altitude/speed to reduce transitions
- If photo pass needed (A14), schedule on a separate battery with photo mode settings

---

### B6. Drone Selection Rationale

**Question:** Which drone is best for this job and why?

| Drone | Best for | Limitation |
|-------|----------|------------|
| Mini 4 Pro / Mini 5 Pro | Residential, low-risk, < 250g exemptions | Smaller sensor, lower wind resistance |
| Air 3 / Air 3S | General videography, good balance | Mid-range flight time |
| Mavic 3 / 3 Classic | Low light, large subjects, long flights | Heavier, more regulatory requirements |
| Mavic 3 Pro | Multi-lens flexibility, varied shot types | Premium cost |
| Mavic 4 Pro | Latest sensor, extended range | Premium cost |

**Decision matrix:**

| Factor | Check |
|--------|-------|
| Lighting conditions (A11) | Low light → larger sensor (Mavic 3 family) |
| Subject size (A3) | Large subject → longer flight time needed |
| Wind conditions (site check) | High wind → heavier drone |
| Regulatory class needed | Urban/populated → sub-250g (Mini series) preferred |
| Frame rate needs (B1) | 120 fps 4K → check drone capability |
| Multiple focal lengths needed | → Mavic 3 Pro |

> **Existing field overlap:** `flight_plan.drone_model` and `order.equipment_id` (FK to
> PilotEquipment). This provides the decision framework behind the selection.

---

### B7. Site Complexity Assessment

**Question:** Rate the site difficulty and note specific challenges.

| Factor | Low | Medium | High |
|--------|-----|--------|------|
| Obstacles (trees, wires, structures) | Open field | Some obstacles, clear paths | Dense obstacles, tight spaces |
| Airspace | Uncontrolled, no restrictions | FRZ — authorization needed | Controlled / restricted |
| People / public | Uninhabited area | Sparse, manageable | Crowds, events |
| Terrain | Flat | Gentle slopes | Steep, uneven, water |
| RF interference | Rural, clear | Suburban | Urban, industrial |
| Access for takeoff/landing | Multiple clear options | Limited options | Single constrained pad |

**Actions based on complexity:**
- High obstacle density → reduce speed_ms, increase standoff, plan manual segments
- Controlled airspace → file authorization before shoot day
- Crowds → fly at regulatory minimum height, plan noise-sensitive windows
- Rough terrain → scout landing zones, bring landing pad

> **Existing field overlap:** `order.environment_type`, `order.proximity_to_people`,
> `order.proximity_to_buildings`, `order.airspace_type` cover regulatory aspects.
> This provides the operational planning layer.

---

### B8. Separate Photo Pass Needed?

**Question:** Does this job require a dedicated photo pass in addition to video?

| Scenario | Decision |
|----------|----------|
| Customer selected "Photos + Video" (A14) | Yes — separate battery, switch to photo mode |
| Customer needs marketing stills | Yes — plan grid or orbit with `action_type: takePhoto` |
| Customer wants video only | No — extract frames from 4K if stills needed |
| Real estate listing with MLS photos | Yes — nadir + oblique photo pass per standard workflow |

**If yes, configure:**
- Photo mode: single / interval / panorama
- Overlap requirements (for photogrammetry): front 80%, side 60%
- Separate altitude (photo pass often higher than video pass)
- Camera angle (nadir for mapping, 45° for oblique)

> **Existing field overlap:** `photo_mode` (single / interval / panorama),
> `camera_angle` (straight_down / 45deg / horizontal / pilot_decides).

---

### B9. Transition Style to Turn Mode Selection

**Question:** Based on the customer's mood (A7) and pacing (A8), select the appropriate
turn mode for each pattern.

| Turn mode | DJI parameter | Visual effect | Best for |
|-----------|--------------|---------------|----------|
| **Stop & turn** | `toPointAndStopWithDiscontinuityCurvature` | Drone stops at each waypoint, sharp direction changes | Inspection, photo capture, hyperlapse |
| **Smooth curve** | `toPointAndPassWithContinuityCurvature` | Drone curves through waypoints without stopping | Cinematic video, orbits, tracking, reveals |

**Mood-to-turn-mode mapping:**

| Mood (from A7) | Recommended turn mode | turn_damping_dist |
|----------------|----------------------|-------------------|
| Epic / Grand | Smooth curve | 5–10 m |
| Calm / Peaceful | Smooth curve | 8–15 m |
| Energetic / Dynamic | Smooth curve | 2–5 m |
| Intimate / Personal | Smooth curve | 3–8 m |
| Dramatic / Cinematic | Smooth curve | 5–12 m |
| Documentary / Neutral | Either — depends on shot | 3–5 m |
| Playful / Fun | Mix of both | Varies |

**Note:** Higher `turn_damping_dist` values produce wider, smoother curves. For video,
almost always use "Smooth curve" unless the shot requires a deliberate pause (e.g.,
hover to reframe).

> **Existing field overlap:** `waypoint.turn_mode` and `waypoint.turn_damping_dist`
> already exist. This maps creative intent to those technical parameters.

---

## PART C — Reference Tables

---

### Table 1: Shot Type to Mission Pattern Mapping

| # | Shot type | Mission pattern | Key parameters | Waypoint notes |
|---|----------|----------------|----------------|----------------|
| 1 | **High-altitude reveal** | Cable Cam | altitude: 80–120 m, speed: 2–4 m/s | 2 points; start with gimbal at 0° (horizon), tilt to -45° at endpoint. `action_type: startRecord` at WP1 |
| 2 | **Low-altitude flyover** | Cable Cam | altitude: 5–15 m, speed: 4–8 m/s | 2+ points along terrain; gimbal -15° to -45°. Requires obstacle-free corridor |
| 3 | **Pull-back reveal** | Cable Cam | altitude: start 10 m → end 40 m, speed: 2–3 m/s | 2 points; ascending line. Gimbal tracks POI via `poi_lat/poi_lng`. Smooth curve turn mode |
| 4 | **Top-down / bird's eye** | Cable Cam or Grid | altitude: 30–60 m, speed: 3–5 m/s | Gimbal locked at -90°. For large areas use Grid pattern (parallel, nadir) |
| 5 | **Panoramic sweep** | Orbit (partial) | radius: N/A (stationary), speed: N/A | Single waypoint with slow yaw rotation (heading 0°→360°). `hover_time_s: 15–30` for full rotation. Or use Orbit with large radius and few points |
| 6 | **Full orbit** | Orbit | radius: per Table 3, speed: 3–5 m/s, num_points: 12–24 | `poi_lat/poi_lng` set to subject center. Direction: cw or ccw based on sun position |
| 7 | **Partial orbit (arc)** | Orbit | radius: per Table 3, speed: 3–5 m/s, num_points: 4–8 | Generate full orbit, delete waypoints outside desired arc (90°–180°) |
| 8 | **Ascending orbit (spiral)** | Spiral | radius: per Table 3, start_alt: 10 m, end_alt: 60 m, speed: 3–4 m/s | `num_revolutions: 1–2`. Gimbal auto-adjusts if POI set. Dramatic reveal effect |
| 9 | **Descending orbit (spiral)** | Spiral | Same as ascending, reverse altitudes | Good for closing shots. Can also fly ascending orbit in reverse |
| 10 | **Multi-altitude orbit** | Multi-Orbit | radius: per Table 3, min_alt: 15 m, max_alt: 60 m, step: 15 m | Creates stacked rings. Good for tall buildings — gives editor altitude options |
| 11 | **Cable cam (A-to-B)** | Cable Cam | altitude: varies, speed: 3–6 m/s, num_points: 2–4 | Straight line with optional intermediate points for gentle curves. Use smooth curve turn mode |
| 12 | **Tracking / follow** | Cable Cam (pre-planned path) | altitude: 5–20 m, speed: match subject | Plan path along expected subject route. Set `poi_lat/poi_lng` to waypoints ahead of subject. May require manual pilot input |
| 13 | **Lead shot** | Cable Cam | altitude: 3–10 m, speed: match subject | Drone ahead of subject, gimbal facing back (heading reversed). Advanced — often manual |
| 14 | **Lateral dolly** | Cable Cam | altitude: 5–30 m, speed: 2–5 m/s | 2 points perpendicular to subject. Gimbal fixed toward subject via POI |
| 15 | **Fly-through** | Cable Cam | altitude: varies, speed: 2–4 m/s | Multiple closely-spaced waypoints through gap. Requires precise GPS and low wind. **High risk — assess carefully** |
| 16 | **Close-up detail** | Cable Cam or Manual | altitude: 3–15 m, speed: 1–3 m/s | Short path near feature. Low speed, tight gimbal angle. May need manual for precision |
| 17 | **Facade scan** | Facade Scan | standoff: 5–15 m, col_spacing: 3–5 m, alt_step: 3–5 m | Automated vertical columns along face. Set `action_type: startRecord`. Good for architectural detail |
| 18 | **Roof / top inspection** | Grid (parallel) or Oblique Grid (nadir) | altitude: 15–30 m, spacing: per GSD, speed: 2–4 m/s | Gimbal at -90°. If video, use wider spacing than photo survey |
| 19 | **Dronie (selfie pull-back)** | Cable Cam | altitude: start 2 m → end 30 m, speed: 2–4 m/s | 2 points; ascending diagonal away from subject. POI on subject. Very popular social media shot |
| 20 | **Hyperlapse** | Orbit or Cable Cam | altitude: varies, speed: N/A | Use `action_type: takePhoto` at intervals. Assembled in post. `turn_mode: stop` for sharp frames. 2–5 sec interval |
| 21 | **Parallax slide** | Cable Cam | altitude: 10–30 m, speed: 1–3 m/s | Lateral movement with foreground/background depth. Gimbal aimed at mid-ground subject via POI. Slow speed essential for parallax effect |

---

### Table 2: Pacing to speed_ms Defaults

| Pacing (from A8) | speed_ms range | Default | Typical use case |
|-------------------|---------------|---------|-----------------|
| Very slow | 1–2 m/s | 1.5 | Luxury property, wellness, spa |
| Slow | 2–4 m/s | 3.0 | Real estate walkthrough, brand film |
| Medium | 4–6 m/s | 5.0 | Events, tourism, general content |
| Fast | 6–10 m/s | 8.0 | Sports, action, music video |
| Mixed | Per shot | — | Use shot-specific values from Table 1 |

**Adjustment factors:**
- Orbit shots: reduce speed by 30% vs linear shots (curvature feels faster)
- Close-up shots: reduce speed by 50% (proximity amplifies perceived speed)
- Reveal shots: start 50% slower, accelerate through the reveal
- Wind conditions: reduce planned speed by wind speed to maintain ground track consistency

---

### Table 3: Subject Scale to radius_m Defaults

| Subject size (from A3) | radius_m (orbit) | standoff_m (facade) | orbit_altitude_m | Notes |
|------------------------|-------------------|---------------------|------------------|-------|
| Small (< 5 m) | 12–18 m | 5–8 m | 8–20 m | Statue, car, small structure |
| Medium (5–30 m) | 20–35 m | 8–15 m | 15–40 m | House, pool, small building |
| Large (30–100 m) | 40–70 m | 15–25 m | 30–80 m | Large building, sports field |
| Very large (> 100 m) | 80–120 m | 25–40 m | 60–120 m | Skyscraper, campus, estate |

**Adjustment factors:**
- Add 5–10 m to radius for each obstacle ring (trees, fences) around subject
- Multiply radius by 1.5 for moving subjects (safety buffer)
- Reduce radius by 20% if using a long focal length drone (Mavic 3 series — 24 mm equiv. vs Mini's wider lens)
- Increase altitude by 10 m if subject has protruding elements (antennas, chimneys)

---

### Table 4: Existing FlyingPlan Fields That Already Cover Some Questions

This table maps questionnaire items to existing database fields to avoid confusion during
implementation and to identify which questions would require new fields if added to the
intake form.

| Questionnaire item | Existing field | Model | Current options | Gap |
|-------------------|---------------|-------|-----------------|-----|
| Project type (A1) | `JobType.code` | JobType | aerial_photo, inspection, survey, event_celebration, real_estate, construction, agriculture, emergency_insurance, custom_other | Missing: brand_film, social_media, music_video, documentary, tourism |
| Footage purpose (A1) | `PurposeOption.code` | PurposeOption | marketing, insurance, progress_report, personal, social_media, real_estate_listing, legal_evidence, other | Adequate for most cases |
| Resolution (B1) | `flight_plan.video_resolution` | FlightPlan | 4k, 1080p | Missing: frame rate |
| Shot types (A5) | `flight_plan.shot_types` | FlightPlan | overview, close_up, orbit, tracking (JSON) | Only 4 options — needs expansion to ~20 |
| Camera angle | `flight_plan.camera_angle` | FlightPlan | straight_down, 45deg, horizontal, pilot_decides | Adequate — maps to gimbal_pitch_deg |
| Photo mode | `flight_plan.photo_mode` | FlightPlan | single, interval, panorama | Adequate |
| Output format (A14) | `output_format` | FlightPlan/Order | raw, edited_video, photos_only, photos_video, livestream | Missing: social_media_package |
| Drone selection (B6) | `flight_plan.drone_model` | FlightPlan | mini_4_pro, mini_5_pro, mavic_3, mavic_3_pro, mavic_3_classic, mavic_4_pro, air_3, air_3s | Adequate |
| Time of day (A11) | `order.time_of_day` | Order | day, night, twilight | Missing: golden_hour, blue_hour, sunrise, sunset |
| Speed (A8) | `waypoint.speed_ms` | Waypoint | Float, default 5.0 | Per-waypoint — no per-mission default |
| Altitude | `waypoint.altitude_m` | Waypoint | Float, default 30.0 | Per-waypoint — no per-mission default |
| Gimbal angle | `waypoint.gimbal_pitch_deg` | Waypoint | Float, default -90.0 | Per-waypoint |
| Turn mode (B9) | `waypoint.turn_mode` | Waypoint | stop (default), smooth curve | Per-waypoint |
| Turn smoothing (B9) | `waypoint.turn_damping_dist` | Waypoint | Float, default 0.0 | Per-waypoint |
| Hover time | `waypoint.hover_time_s` | Waypoint | Float, default 0.0 | Per-waypoint |
| POI tracking | `waypoint.poi_lat`, `waypoint.poi_lng` | Waypoint | Float, nullable | Per-waypoint |
| Action trigger | `waypoint.action_type` | Waypoint | takePhoto, startRecord, stopRecord | Per-waypoint |
| Site environment (B7) | `order.environment_type` | Order | open_countryside, suburban, urban, industrial, congested | Adequate for regulatory |
| People proximity (B7) | `order.proximity_to_people` | Order | over_uninvolved, near_under_50m, 50m_plus, over_crowds, controlled_area | Adequate for regulatory |
| Airspace (B7) | `order.airspace_type` | Order | uncontrolled, frz, controlled, restricted, danger | Adequate |
| Aspect ratio (A2) | — | — | — | **New field needed** |
| Duration (A2) | — | — | — | **New field needed** |
| Subject description (A3) | — | — | — | **New field needed** |
| Mood / style (A7) | — | — | — | **New field needed** |
| Pacing (A8) | — | — | — | **New field needed** |
| Color profile (B2) | — | — | — | **New field needed** |
| Frame rate (B1) | — | — | — | **New field needed** |
| Audio considerations (A12) | — | — | — | **New field needed** |

---

*End of questionnaire. This document should be reviewed and updated as new mission patterns
are added to FlyingPlan or as customer feedback reveals missing questions.*
