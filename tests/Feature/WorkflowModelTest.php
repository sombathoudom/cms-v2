<?php

use App\Models\PublishQueue;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\Content;
use App\Models\User;

it('persists workflow definitions and actions', function () {
    $admin = User::factory()->create();

    $workflow = Workflow::create([
        'name' => 'Editorial Flow',
        'description' => 'Draft to publish',
    ]);

    $step = WorkflowStep::create([
        'workflow_id' => $workflow->id,
        'name' => 'Review',
        'position' => 1,
        'role' => 'Editor',
    ]);

    $content = Content::factory()->create(['author_id' => $admin->id]);

    $instance = WorkflowInstance::create([
        'workflow_id' => $workflow->id,
        'content_id' => $content->id,
        'status' => 'review',
    ]);

    WorkflowAction::create([
        'workflow_instance_id' => $instance->id,
        'user_id' => $admin->id,
        'action' => 'approve',
    ]);

    PublishQueue::create([
        'content_id' => $content->id,
        'publish_at' => now()->addDay(),
        'status' => 'pending',
    ]);

    expect($workflow->steps)->toHaveCount(1);
    expect($instance->actions)->toHaveCount(1);
});
