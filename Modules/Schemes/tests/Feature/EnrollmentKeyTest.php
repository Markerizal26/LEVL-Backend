<?php

namespace Modules\Schemes\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class EnrollmentKeyTest extends TestCase
{
  use RefreshDatabase;

  private User $admin;

  private User $student;

  private Course $course;

  protected function setUp(): void
  {
    parent::setUp();

    // Create roles first
    $guard = "api";
    \Spatie\Permission\Models\Role::firstOrCreate(["name" => "Superadmin", "guard_name" => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(["name" => "Admin", "guard_name" => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(["name" => "Instructor", "guard_name" => $guard]);
    \Spatie\Permission\Models\Role::firstOrCreate(["name" => "Student", "guard_name" => $guard]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole("Admin");

    $this->student = User::factory()->create();
    $this->student->assignRole("Student");

    $this->course = Course::factory()->create([
      "instructor_id" => $this->admin->id,
      "enrollment_type" => "auto_accept",
      "enrollment_key" => null,
    ]);
  }

  /** @test */
  public function admin_can_generate_enrollment_key()
  {
    $response = $this->actingAs($this->admin, "api")->postJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key/generate",
    );

    $response
      ->assertStatus(200)
      ->assertJsonStructure([
        "success",
        "data" => ["enrollment_key", "course" => ["id", "slug", "title", "enrollment_key"]],
      ]);

    $this->assertNotNull($response->json("data.enrollment_key"));
    $this->assertEquals(12, strlen($response->json("data.enrollment_key")));

    // Verify database updated
    $this->course->refresh();
    $this->assertEquals($response->json("data.enrollment_key"), $this->course->enrollment_key);
  }

  /** @test */
  public function student_cannot_generate_enrollment_key()
  {
    $response = $this->actingAs($this->student, "api")->postJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key/generate",
    );

    $response->assertStatus(403);
  }

  /** @test */
  public function admin_can_update_enrollment_type_to_key_based()
  {
    $response = $this->actingAs($this->admin, "api")->putJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
      [
        "enrollment_type" => "key_based",
        "enrollment_key" => "CUSTOMKEY123",
      ],
    );

    $response->assertStatus(200)->assertJson([
      "success" => true,
      "data" => [
        "enrollment_key" => "CUSTOMKEY123",
      ],
    ]);

    $this->course->refresh();
    $this->assertEquals("key_based", $this->course->enrollment_type);
    $this->assertEquals("CUSTOMKEY123", $this->course->enrollment_key);
  }

  /** @test */
  public function enrollment_type_auto_accept_clears_key()
  {
    $this->course->update([
      "enrollment_type" => "key_based",
      "enrollment_key" => "SOMEKEY",
    ]);

    $response = $this->actingAs($this->admin, "api")->putJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
      [
        "enrollment_type" => "auto_accept",
      ],
    );

    $response->assertStatus(200);

    $this->course->refresh();
    $this->assertEquals("auto_accept", $this->course->enrollment_type);
    $this->assertNull($this->course->enrollment_key);
  }

  /** @test */
  public function key_based_without_key_auto_generates()
  {
    $response = $this->actingAs($this->admin, "api")->putJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
      [
        "enrollment_type" => "key_based",
      ],
    );

    $response->assertStatus(200);

    $this->course->refresh();
    $this->assertEquals("key_based", $this->course->enrollment_type);
    $this->assertNotNull($this->course->enrollment_key);
    $this->assertEquals(12, strlen($this->course->enrollment_key));
  }

  /** @test */
  public function admin_can_remove_enrollment_key()
  {
    $this->course->update([
      "enrollment_type" => "key_based",
      "enrollment_key" => "TESTKEY",
    ]);

    $response = $this->actingAs($this->admin, "api")->deleteJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
    );

    $response->assertStatus(200);

    $this->course->refresh();
    $this->assertEquals("auto_accept", $this->course->enrollment_type);
    $this->assertNull($this->course->enrollment_key);
  }

  /** @test */
  public function student_cannot_update_enrollment_key()
  {
    $response = $this->actingAs($this->student, "api")->putJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
      [
        "enrollment_type" => "key_based",
        "enrollment_key" => "TEST",
      ],
    );

    $response->assertStatus(403);
  }

  /** @test */
  public function invalid_enrollment_type_rejected()
  {
    $response = $this->actingAs($this->admin, "api")->putJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
      [
        "enrollment_type" => "invalid_type",
      ],
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["enrollment_type"]);
  }

  /** @test */
  public function enrollment_key_max_length_validation()
  {
    $longKey = str_repeat("A", 101); // 101 characters

    $response = $this->actingAs($this->admin, "api")->putJson(
      "/api/v1/courses/{$this->course->slug}/enrollment-key",
      [
        "enrollment_type" => "key_based",
        "enrollment_key" => $longKey,
      ],
    );

    $response->assertStatus(422)->assertJsonValidationErrors(["enrollment_key"]);
  }
}
