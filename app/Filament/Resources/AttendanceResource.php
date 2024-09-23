<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Attendance Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make([
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->disabled()
                                ->required(),
                            Forms\Components\TextInput::make('schedule_latitude')
                                ->required(),
                            Forms\Components\TextInput::make('schedule_longitude')
                                ->required(),
                            Forms\Components\TextInput::make('schedule_start_time')
                                ->required(),
                            Forms\Components\TextInput::make('schedule_end_time')
                                ->required(),
                            Forms\Components\TextInput::make('start_latitude')
                                ->required(),
                        ])
                    ]),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make([

                        Forms\Components\TextInput::make('start_longitude')
                            ->required(),
                        Forms\Components\TextInput::make('end_latitude'),
                        Forms\Components\TextInput::make('end_longitude'),
                        Forms\Components\TextInput::make('start_time'),
                        Forms\Components\TextInput::make('end_time'),
                    ])
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');
                if (!$is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_late')
                    ->label('Status')
                    ->alignLeft()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->isLate() ? 'Terlambat' : 'Tepat Waktu';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Terlambat' => 'danger',
                        'Tepat Waktu' => 'success',
                    })
                    ->description(fn(Attendance $record): string => 'Durasi : ' . $record->workDuration()),
                // Tables\Columns\TextColumn::make('is_early_clock_in')
                //     ->label('Early Clock In')
                //     ->badge()
                //     ->getStateUsing(function ($record) {
                //         return $record->isEarlyClockIn() ? 'No' : 'Yes';
                //     })
                //     ->color(fn(string $state): string => match ($state) {
                //         'No' => 'danger',
                //         'Yes' => 'success',
                //     }),
                Tables\Columns\IconColumn::make('is_early_clock_in')
                    ->label('Early Clock In')
                    ->boolean()
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return !$record->isEarlyClockIn();
                    }),
                Tables\Columns\TextColumn::make('start_time')->label('Clock In')
                    ->description(fn(Attendance $record): ?string => $record->start_notes),
                Tables\Columns\TextColumn::make('end_time')->label('Clock Out')
                    ->description(fn(Attendance $record): ?string => $record->end_notes),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->label('Options'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
