<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_see_the_profile_of_an_individual_student()
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.student.show', $student));

        $response->assertOk();
        $response->assertViewIs('admin.student.show');
        $response->assertViewHas('student', $student);
        $response->assertSee($student->full_name);
    }

    /** @test */
    public function admins_can_edit_a_students_information()
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.student.update', $student), [
            'forenames' => 'Hello',
            'surname' => 'Kitty',
            'email' => 'hellokitty@example.com',
            'is_silenced' => true,
            'silenced_reason' => 'This is a reason',
            'is_active' => false,
            'new_note' => 'This is a note',
        ]);

        $response->assertRedirect(route('admin.student.show', $student));
        $response->assertSessionHasNoErrors();
        tap($student->fresh(), function ($student) use ($admin) {
            $this->assertEquals('Hello', $student->forenames);
            $this->assertEquals('Kitty', $student->surname);
            $this->assertEquals('hellokitty@example.com', $student->email);
            $this->assertTrue($student->is_silenced);
            $this->assertEquals('This is a reason', $student->silenced_reason);
            $this->assertFalse($student->is_active);
            $this->assertEquals(1, $student->notes()->count());
            $this->assertEquals('This is a note', $student->notes->first()->body);
            $this->assertTrue($student->notes->first()->user->is($admin));
        });
    }

    /** @test */
    public function admins_cant_edit_a_students_information_with_duff_data()
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create(['email' => 'student@example.com']);

        $response = $this->actingAs($admin)->from(route('admin.student.show', $student))->post(route('admin.student.update', $student), [
            'forenames' => '',
            'surname' => '',
            'email' => 'hellokitty',
            'is_silenced' => 15,
            'silenced_reason' => '',
            'is_active' => -10,
            'new_note' => Str::random(2048),
        ]);

        $response->assertRedirect(route('admin.student.show', $student));
        $response->assertSessionHasErrors([
            'forenames',
            'surname',
            'email',
            'is_silenced',
            'is_active',
            'new_note',
        ]);
        $this->assertEquals('student@example.com', $student->fresh()->email);
    }

    /** @test */
    public function admins_can_edit_notes()
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $note1 = $student->notes()->create([
            'body' => 'This is a note',
            'user_id' => $admin->id,
        ]);
        $note2 = $student->notes()->create([
            'body' => 'This is another note',
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.student.notes.update', $note2), [
            'body' => 'This is an updated note',
        ]);

        $response->assertRedirect(route('admin.student.show', $student));
        $response->assertSessionDoesntHaveErrors();
        $this->assertEquals(2, $student->notes()->count());
        $this->assertEquals('This is a note', $student->fresh()->notes->first()->body);
        $this->assertTrue($student->fresh()->notes->first()->user->is($admin));
        $this->assertEquals('This is an updated note', $student->fresh()->notes->last()->body);
        $this->assertTrue($student->fresh()->notes->first()->user->is($admin));
    }

    /** @test */
    public function admins_can_delete_notes_from_students()
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();
        $note1 = $student->notes()->create([
            'body' => 'This is a note',
            'user_id' => $admin->id,
        ]);
        $note2 = $student->notes()->create([
            'body' => 'This is another note',
            'user_id' => $admin->id,
        ]);
        $response = $this->actingAs($admin)->post(route('admin.student.notes.delete', $note2));

        $response->assertRedirect(route('admin.student.show', $student));
        $response->assertSessionDoesntHaveErrors();
        $this->assertEquals(1, $student->notes()->count());
        $this->assertEquals('This is a note', $student->fresh()->notes->first()->body);
        $this->assertTrue($student->fresh()->notes->first()->user->is($admin));
    }
}
