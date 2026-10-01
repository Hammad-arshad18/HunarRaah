<?php

namespace App\Filament\Resources;

use App\Filament\Support\AdminWorkflows as Workflow;
use App\Filament\Support\StudioResource;
use App\Http\Controllers\AdminOperationsController;
use App\Models\Order;
use Filament\Tables\Actions as A;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends StudioResource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Student records';

    public static function table(Table $table): Table
    {
        return $table->columns([C\TextColumn::make('public_reference')->label('Order reference')->searchable()->copyable(), C\TextColumn::make('user.name')->label('Student')->searchable(), C\TextColumn::make('title_snapshot')->label('Course')->searchable()->wrap(), C\TextColumn::make('payment_status')->badge(), C\TextColumn::make('total_minor')->label('Total')->formatStateUsing(fn ($state, Order $record) => $record->currency.' '.number_format($state / 100, 2)), C\TextColumn::make('refunded_minor')->label('Refunded')->formatStateUsing(fn ($state, Order $record) => $record->currency.' '.number_format($state / 100, 2)), C\TextColumn::make('dispute_status')->badge(), C\TextColumn::make('created_at')->dateTime()->sortable()])->filters([SelectFilter::make('payment_status')->native(false)->options(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'expired' => 'Expired', 'refunded' => 'Refunded']), SelectFilter::make('dispute_status')->native(false)->options(['none' => 'None', 'open' => 'Open', 'won' => 'Won', 'lost' => 'Lost'])])->actions([
            A\Action::make('reconcile')->label('Reconcile with Stripe')->requiresConfirmation()->modalDescription('Queues a provider check. The worker must be running and Stripe credentials configured. Refunds are performed in Stripe Dashboard.')->action(fn (Order $record) => Workflow::run(AdminOperationsController::class, 'reconcile', [], $record)),
        ])->defaultSort('id', 'desc')->emptyStateDescription('Orders appear when students begin a paid checkout. Complimentary enrollment never creates fake revenue.');
    }

    public static function getPages(): array
    {
        return ['index' => OrderResource\Pages\ListOrders::route('/')];
    }
}
