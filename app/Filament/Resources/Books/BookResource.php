<?php

namespace App\Filament\Resources\Books;

use App\Filament\Resources\Books\Pages\ManageBooks;
use App\Models\Book;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(180),
                Select::make('vendor_id')->relationship('vendor', 'name')->searchable()->preload()->required(),
                TextInput::make('slug')->required()->maxLength(220)->unique(ignoreRecord: true),
                Textarea::make('description')->required()->rows(5)->columnSpanFull(),
                TextInput::make('cover_image')->url()->maxLength(2048),
                Select::make('formats')->options(['pdf' => 'PDF', 'epub' => 'EPUB', 'audio' => 'Audio'])->multiple()->required(),
                TextInput::make('price_minor')->numeric()->required()->minValue(0),
                TextInput::make('currency')->required()->length(3)->default('PKR'),
                TextInput::make('category')->required()->maxLength(100),
                TextInput::make('isbn')->maxLength(20)->unique(ignoreRecord: true),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->required(),
                Toggle::make('digital_available')->default(true),
                Toggle::make('physical_available'),
                TextInput::make('stock_quantity')->numeric()->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('vendor.name')->label('Vendor')->searchable(),
                TextColumn::make('category')->sortable(),
                TextColumn::make('price_minor')->money(fn (Book $record): string => $record->currency),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => ManageBooks::route('/'),
        ];
    }
}
