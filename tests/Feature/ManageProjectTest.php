<?php

namespace Tests\Feature;

use App\Project;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManageProjectTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /** @test */

    public function guests_cannot_manage_projects()
    {
        $project = factory('App\Project')->create();

        $this->get('/projects')->assertRedirect('login');
        $this->get('/projects/create')->assertRedirect('login');
        $this->get($project->path())->assertRedirect('login');
        $this->get($project->path() . '/edit')->assertRedirect('login');
        $this->post('/projects', $project->toArray())->assertRedirect('login');
    }

    /** @test */

    public function a_user_can_create_project()
    {
        $this->signIn();

        $this->get('/projects/create')->assertStatus(200);

        $attributes = factory(Project::class)->raw();

        $response = $this->followingRedirects()->post('/projects', $attributes);

        $response
            ->assertSee($attributes['title'])
            ->assertSee(Str::limit($attributes['description'], 100))
            ->assertSee($attributes['notes']);

    }

    /** @test */

    public function tasks_can_be_included_as_part_a_new_project_creation()
    {
        $this->signIn();

        $attributes = factory(Project::class)->raw();

        $attributes['tasks'] = [
            ['body' => 'task 1'],
            ['body' => 'task 2'],
        ] ;

        $this->post('/projects', $attributes);

        $this->assertCount(2, Project::first()->tasks);
    }


    /** @test */

    public function a_user_can_see_all_projects_they_have_been_invited_to_on_their_dashboard()
    {
        $user = $this->signIn();

        $project = ProjectFactory::create();
        $project->invite($user);

        $this->get('/projects')->assertSee($project->title);
    }

    /** @test */

    public function unauthorized_users_cannot_delete_project()
    {
        $project = ProjectFactory::create();

        $this->delete($project->path())->assertRedirect('/login');

        $user = $this->signIn();

        $this->delete($project->path())->assertStatus(403);

        $project->invite($user);

        $this->actingAs($user)->delete($project->path())->assertStatus(403);
    }

    /** @test */

    public function a_user_can_delete_project()
    {
        $project = ProjectFactory::create();

        $this->actingAs($project->owner)
            ->delete($project->path())
            ->assertRedirect('/projects');

        $this->assertDatabaseMissing('projects' , $project->only('id'));
    }

    /** @test */

    public function a_user_can_update_project()
    {
        $project = ProjectFactory::create();

        $this->actingAs($project->owner)->patch($project->path(), $attributes = [
            'notes' => 'changed',
            'title' => 'changed',
            'description' => 'changed'
        ])->assertRedirect($project->path());

        $this->get($project->path() . '/edit')->assertOk();

        $this->assertDatabaseHas('projects', $attributes);

    }

    /** @test */

    public function a_user_can_update_projects_general_notes()
    {
        $project = ProjectFactory::create();

        $this->actingAs($project->owner)->patch($project->path(), $attributes = ['notes' => 'changed',]);

        $this->assertDatabaseHas('projects', $attributes);
    }

    /** @test */

    public function a_user_can_view_their_project()
    {
        $project = ProjectFactory::create();

        $this->actingAs($project->owner)->get($project->path())
            ->assertSee($project->title)
            ->assertSee(Str::limit($project->description, 100));

    }

    /** @test */

    public function an_authenticated_user_cannot_view_the_projects_of_others()
    {
        $this->signIn();

        $project = factory('App\Project')->create();

        $this->get($project->path())->assertStatus(403);
    }

    /** @test */

    public function an_authenticated_user_cannot_update_the_projects_of_others()
    {
        $this->signIn();
        $project = factory('App\Project')->create();

        $this->patch($project->path())->assertStatus(403);
    }

    /** @test */

    public function a_project_requires_a_title()
    {
        $this->signIn();

        $attributes = factory('App\Project')->raw(['title' => '']);

        $this->post('/projects', $attributes)->assertSessionHasErrors('title');

    }

    /** @test */

    public function a_project_requires_a_description()
    {
        $this->signIn();

        $attributes = factory('App\Project')->raw(['description' => '']);

        $this->post('/projects', $attributes)->assertSessionHasErrors('description');

    }


}
