<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectInvitation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Team Members';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('role')
                    ->options([
                        'Product Manager' => 'Product Manager',
                        'Developer' => 'Developer',
                        'Viewer' => 'Viewer',
                    ])
                    ->default('Developer')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Product Manager' => 'danger',
                        'Developer' => 'warning',
                        'Viewer' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        // Only allow editing the role
                        return [
                            'role' => $data['role'] ?? 'Developer',
                        ];
                    }),
                DetachAction::make()
                    ->label('Remove from project')
                    ->before(function () {
                        // Prevent owner from removing themselves
                        if ($this->getOwnerRecord()->owner_id === Auth::id()) {
                            if ($this->getRecord()->id === Auth::id()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot Remove Owner')
                                    ->body('Project owners cannot remove themselves from the project.')
                                    ->send();

                                return false;
                            }
                        }
                    }),
            ])
            ->toolbarActions([
                // Add existing user
                AttachAction::make('addMember')
                    ->label('Add Team Member')
                    ->icon('heroicon-o-user-plus')
                    ->recordSelectOptionsQuery(fn ($query) => $query->whereNotIn('id',
                        $this->ownerRecord->members()->select('user_id')
                    )
                    )
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Team Member')
                            ->required()
                            ->searchable()
                            ->preload(),
                        \Filament\Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'Product Manager' => 'Product Manager',
                                'Developer' => 'Developer',
                                'Viewer' => 'Viewer',
                            ])
                            ->default('Developer')
                            ->required(),
                    ])
                    ->successNotificationTitle('Team member added successfully'),

                // Invite new user via email
                Action::make('inviteMember')
                    ->label('Invite via Email')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique('users', 'email')
                            ->validationMessages([
                                'unique' => 'This user already exists in the system. Please use "Add Team Member" instead.',
                            ]),
                        \Filament\Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'Product Manager' => 'Product Manager',
                                'Developer' => 'Developer',
                                'Viewer' => 'Viewer',
                            ])
                            ->default('Developer')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Check if user already exists
                        $existingUser = User::where('email', $data['email'])->first();

                        if ($existingUser) {
                            // Attach existing user
                            if ($this->ownerRecord->members()->where('user_id', $existingUser->id)->exists()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Already a Member')
                                    ->body('This user is already a member of this project.')
                                    ->send();

                                return;
                            }

                            $this->ownerRecord->members()->attach($existingUser->id, [
                                'role' => $data['role'],
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Team Member Added')
                                ->body("{$existingUser->name} has been added to the project.")
                                ->send();
                        } else {
                            // Check for pending invitation
                            $pendingInvitation = ProjectInvitation::where('email', $data['email'])
                                ->where('project_id', $this->ownerRecord->id)
                                ->pending()
                                ->first();

                            if ($pendingInvitation) {
                                Notification::make()
                                    ->warning()
                                    ->title('Invitation Already Sent')
                                    ->body('An invitation has already been sent to this email address.')
                                    ->send();

                                return;
                            }

                            // Create new invitation
                            $invitation = ProjectInvitation::create([
                                'project_id' => $this->ownerRecord->id,
                                'email' => $data['email'],
                                'role' => $data['role'],
                                'token' => Str::uuid(),
                                'status' => 'pending',
                                'invited_by' => Auth::id(),
                                'expires_at' => now()->addDays(7),
                            ]);

                            // Send notification email
                            \Illuminate\Support\Facades\Notification::route('mail', $data['email'])
                                ->notify(new \App\Notifications\ProjectInvitationNotification($invitation));

                            Notification::make()
                                ->success()
                                ->title('Invitation Sent')
                                ->body("An invitation has been sent to {$data['email']}.")
                                ->send();
                        }
                    }),
            ]);
    }
}
