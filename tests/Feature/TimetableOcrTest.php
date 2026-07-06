<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TimetableOcrTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hod_can_open_ocr_upload_page(): void
    {
        $this->withoutVite();

        $hod = User::where('username', 'hod')->firstOrFail();

        $this->actingAs($hod)
            ->get(route('timetables.upload-ocr'))
            ->assertOk()
            ->assertSeeText('AI Timetable Uploader');
    }

    public function test_ocr_upload_requires_valid_inputs(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();

        $this->actingAs($hod)
            ->post(route('timetables.process-ocr'), [])
            ->assertSessionHasErrors(['class_section_id', 'timetable_image']);
    }

    public function test_hod_can_upload_and_parse_timetable_via_mocked_gemini_api(): void
    {
        $this->withoutVite();

        $hod = User::where('username', 'hod')->firstOrFail();
        $section = ClassSection::query()->orderBy('id')->firstOrFail();

        // Create a subject and assignment to match against
        $subject = Subject::create([
            'program_id' => $section->program_id,
            'semester_id' => $section->semester_id,
            'subject_code' => 'BCA301',
            'subject_name' => 'Database Management Systems',
            'credits' => 4,
            'status' => 'active',
        ]);
        
        $faculty = Faculty::query()->firstOrFail();
        $faculty->update(['display_initials' => 'KS']);

        $assignment = SubjectAssignment::create([
            'class_section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);

        // Mock Gemini API Response
        $mockPayload = [
            [
                'day_of_week' => 1,
                'start_time' => '09:15',
                'end_time' => '10:15',
                'subject_code' => 'BCA301',
                'faculty_initials' => 'KS',
                'slot_type' => 'regular',
                'lecture_no' => 1,
                'cell_label' => 'Room 101',
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($mockPayload)]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        putenv('GEMINI_API_KEY=AIzaSyFakeKeyForTesting');

        $file = UploadedFile::fake()->create('timetable.png', 10, 'image/png');

        $this->actingAs($hod)
            ->post(route('timetables.process-ocr'), [
                'class_section_id' => $section->id,
                'timetable_image' => $file,
            ])
            ->assertOk()
            ->assertViewIs('timetables.preview_ocr')
            ->assertSeeText('BCA301')
            ->assertSeeText('Room 101')
            ->assertSeeText('Mapped');
            
        putenv('GEMINI_API_KEY=');
    }

    public function test_hod_can_save_confirmed_ocr_slots(): void
    {
        $hod = User::where('username', 'hod')->firstOrFail();
        $section = ClassSection::query()->orderBy('id')->firstOrFail();

        // Clear pre-seeded timetable entries to prevent overlaps
        Timetable::query()->delete();

        $subject = Subject::create([
            'program_id' => $section->program_id,
            'semester_id' => $section->semester_id,
            'subject_code' => 'BCA301',
            'subject_name' => 'Database Management Systems',
            'credits' => 4,
            'status' => 'active',
        ]);
        
        $faculty = Faculty::query()->firstOrFail();

        $assignment = SubjectAssignment::create([
            'class_section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);

        $slotsPayload = [
            [
                'enabled' => '1',
                'subject_assignment_id' => $assignment->id,
                'day_of_week' => 1,
                'slot_type' => 'regular',
                'start_time' => '09:15:00',
                'end_time' => '10:15:00',
                'lecture_no' => 1,
                'cell_label' => 'Room 101',
            ]
        ];

        $this->actingAs($hod)
            ->post(route('timetables.save-ocr'), [
                'class_section_id' => $section->id,
                'slots' => $slotsPayload,
            ])
            ->assertRedirect(route('timetables.index', ['class_section_id' => $section->id]))
            ->assertSessionHas('status', 'Timetable slots successfully imported from image.');

        $this->assertDatabaseHas('timetables', [
            'subject_assignment_id' => $assignment->id,
            'day_of_week' => 1,
            'start_time' => '09:15:00',
            'end_time' => '10:15:00',
            'lecture_no' => 1,
            'cell_label' => 'Room 101',
        ]);
    }
}
