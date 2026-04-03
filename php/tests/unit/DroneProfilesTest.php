<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\DroneProfiles;

/**
 * @internal
 */
final class DroneProfilesTest extends CIUnitTestCase
{
    public function testAllProfilesExist(): void
    {
        $expected = ['mini_4_pro', 'mini_5_pro', 'mavic_3', 'mavic_3_pro',
                     'mavic_3_classic', 'mavic_4_pro', 'air_3', 'air_3s'];

        foreach ($expected as $key) {
            $profile = DroneProfiles::getProfile($key);
            $this->assertNotEmpty($profile['display_name'], "Profile $key should have display_name");
            $this->assertGreaterThan(0, $profile['sensor_width_mm'], "Profile $key sensor width");
            $this->assertGreaterThan(0, $profile['focal_length_mm'], "Profile $key focal length");
            $this->assertGreaterThan(0, $profile['image_width_px'], "Profile $key image width");
        }
    }

    public function testFallbackToDefault(): void
    {
        $profile = DroneProfiles::getProfile('nonexistent');
        $default = DroneProfiles::getProfile(DroneProfiles::DEFAULT_DRONE);
        $this->assertEquals($default, $profile);
    }

    public function testGetChoices(): void
    {
        $choices = DroneProfiles::getChoices();
        $this->assertCount(8, $choices);

        foreach ($choices as $choice) {
            $this->assertCount(2, $choice, 'Each choice should be [key, display_name]');
            $this->assertNotEmpty($choice[0]);
            $this->assertNotEmpty($choice[1]);
        }
    }

    public function testMini4ProSpecs(): void
    {
        $p = DroneProfiles::getProfile('mini_4_pro');
        $this->assertEquals('DJI Mini 4 Pro', $p['display_name']);
        $this->assertEquals(77, $p['droneEnumValue']);
        $this->assertEquals(34, $p['max_flight_time_min']);
    }
}
