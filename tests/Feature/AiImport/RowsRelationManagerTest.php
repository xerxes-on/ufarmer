<?php

declare(strict_types=1);

namespace Tests\Feature\AiImport;

use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Filament\Resources\AiImportResource\RelationManagers\RowsRelationManager;
use Modules\JobsServices\Models\AiImportRow;
use ReflectionClass;
use Tests\TestCase;

/**
 * The review screen must always let an admin correct a draft row (UFARM-2671).
 *
 * Found on admin-dev: a batch that published 4 of its 35 rows was marked
 * Published, and `isReadOnly()` then removed the Edit action from all 31 rows
 * that had not. Filament authorises EditAction on `! isReadOnly()`, so the
 * button vanished with nothing on screen to explain why, and the only remedy —
 * re-parsing — was blocked too.
 *
 * These assertions are deliberately structural rather than a full Livewire
 * mount: the table needs no database to describe itself, and the two things
 * worth pinning are what a Filament upgrade would quietly undo.
 */
class RowsRelationManagerTest extends TestCase
{
    public function test_correcting_a_row_does_not_depend_on_the_batch_status(): void
    {
        // The parent isReadOnly() returns true for every relation manager on a
        // ViewRecord page, so re-introducing an override here — for any reason
        // — takes the Edit action away again.
        $this->assertFalse(
            (new ReflectionClass(RowsRelationManager::class))->hasMethod('isReadOnly')
                && (new ReflectionClass(RowsRelationManager::class))->getMethod('isReadOnly')
                    ->getDeclaringClass()->getName() === RowsRelationManager::class,
            'RowsRelationManager must not override isReadOnly(): it removes the row edit action.',
        );
    }

    public function test_a_draft_row_can_be_edited_and_a_published_one_cannot(): void
    {
        $edit = $this->action('edit_row');

        $this->assertTrue($this->visibleFor($edit, AiImportRowStatus::Draft));

        // A published row already created a user, profile and offer; editing
        // its staged copy would only desync the two.
        $this->assertFalse($this->visibleFor($edit, AiImportRowStatus::Published));
        $this->assertFalse($this->visibleFor($edit, AiImportRowStatus::Skipped));
    }

    public function test_the_edit_action_is_not_a_filament_edit_action(): void
    {
        // An EditAction would be re-gated on isReadOnly() by Filament itself,
        // which is the whole reason this is a plain Action.
        $this->assertNotInstanceOf(
            \Filament\Tables\Actions\EditAction::class,
            $this->action('edit_row'),
        );
    }

    private function action(string $name): Action
    {
        $manager = new RowsRelationManager;
        $table = $manager->table(Table::make($manager));

        foreach ($table->getFlatActions() as $action) {
            if ($action->getName() === $name) {
                return $action;
            }
        }

        $this->fail(sprintf('no "%s" action on the rows table', $name));
    }

    private function visibleFor(Action $action, AiImportRowStatus $status): bool
    {
        $row = new AiImportRow;
        $row->setRawAttributes(['status' => $status->value], true);

        return $action->record($row)->isVisible();
    }
}
