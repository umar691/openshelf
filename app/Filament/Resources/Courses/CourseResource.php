<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages\ManageCourses;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(180),
                Select::make('instructor_id')->relationship('instructor', 'name')->searchable()->preload()->required(),
                TextInput::make('slug')->required()->maxLength(220)->unique(ignoreRecord: true),
                Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                TextInput::make('subject')->required()->maxLength(100),
                Select::make('difficulty')->options(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'])->required(),
                TextInput::make('price_minor')->numeric()->required()->minValue(0),
                TextInput::make('currency')->required()->length(3)->default('PKR'),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('instructor.name')->label('Instructor')->searchable(),
                TextColumn::make('subject')->sortable(),
                TextColumn::make('difficulty')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCourses::route('/'),
        ];
    }
}
