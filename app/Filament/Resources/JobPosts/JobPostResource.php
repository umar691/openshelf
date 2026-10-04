<?php

namespace App\Filament\Resources\JobPosts;

use App\Filament\Resources\JobPosts\Pages\ManageJobPosts;
use App\Models\JobPost;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JobPostResource extends Resource
{
    protected static ?string $model = JobPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(180),
                Select::make('employer_id')->relationship('employer', 'name')->searchable()->preload()->required(),
                TextInput::make('slug')->required()->maxLength(220)->unique(ignoreRecord: true),
                Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                TagsInput::make('requirements')->required(),
                Select::make('employment_type')->options(['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Contract', 'internship' => 'Internship'])->required(),
                Select::make('location_type')->options(['remote' => 'Remote', 'hybrid' => 'Hybrid', 'onsite' => 'On-site'])->required(),
                TextInput::make('location')->maxLength(180),
                TextInput::make('salary_min_minor')->numeric()->minValue(0),
                TextInput::make('salary_max_minor')->numeric()->minValue(0),
                TextInput::make('salary_currency')->required()->length(3)->default('PKR'),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'])->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('employer.name')->label('Employer')->searchable(),
                TextColumn::make('location_type')->badge(),
                TextColumn::make('employment_type'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed']),
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
            'index' => ManageJobPosts::route('/'),
        ];
    }
}
