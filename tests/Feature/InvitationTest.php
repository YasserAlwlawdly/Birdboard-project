<?php

namespace Tests\Feature;

use App\User;
use Facades\Tests\Setup\ProjectFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */

    public function non_owner_can_invite_users()
    {
        $project = ProjectFactory::create();

        $user = factory(User::class)->create();

        $this->actingAs($user)->post($project->path() . '/invitations')
            ->assertStatus(403);

        $project->invite($user);

        $this->actingAs($user)->post($project->path() . '/invitations')
            ->assertStatus(403);

    }

    /** @test */

    public function a_project_owner_can_invite_a_user()
    {
        $project = ProjectFactory::create();

        $userToInvite = factory(User::class)->create();

        $this->actingAs($project->owner)->post($project->path() . '/invitations', [
            'email' => $userToInvite->email
        ])->assertRedirect($project->path());

        $this->assertTrue($project->members->contains($userToInvite));
    }

    /** @test */

    public function the_email_address_must_be_associated_with_a_valid_birdboard_account()
    {
        $project = ProjectFactory::create();

        $this->actingAs($project->owner)->post($project->path() . '/invitations', [
            'email' => 'notauser@example.com',
        ])->assertSessionHasErrors([
            'email' => 'The user you are inviting must have valid Birdboard account.',
        ] , null , 'invitations');

    }

    /** @test */

    public function invite_users_may_update_project_details()
    {
        $project = ProjectFactory::create();

        $project->invite($newUser = factory(User::class)->create());

        $this->signIn($newUser);

        $this->post(action('ProjectTaskController@store', $project), $task = ['body' => 'foo task']);

        $this->assertDatabaseHas('tasks', $task);
    }
}
