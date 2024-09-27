<?php

namespace Vormkracht10\FilamentMails\Resources;

use Filament\Tables;
use Illuminate\View\View;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\Action;
use Vormkracht10\Mails\Models\Mail;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Vormkracht10\Mails\Enums\EventType;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Vormkracht10\Mails\Models\MailEvent;
use Filament\Infolists\Components\Section;
use Vormkracht10\Mails\Actions\ResendMail;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Vormkracht10\FilamentMails\Resources\MailResource\Pages\ViewMail;
use Vormkracht10\FilamentMails\Resources\MailResource\Pages\ListMails;
use Vormkracht10\FilamentMails\Resources\MailResource\Widgets\MailStatsWidget;

class MailResource extends Resource
{
    protected static ?string $model = Mail::class;

    protected static ?string $slug = 'mails';

    protected static ?string $recordTitleAttribute = 'subject';

    protected static bool $isScopedToTenant = false;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationGroup(): ?string
    {
        return __('Mails');
    }

    public static function getNavigationLabel(): string
    {
        return __('Mails');
    }

    public static function getLabel(): ?string
    {
        return __('Mail');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public function getTitle(): string
    {
        return __('Mails');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('General')
                    ->icon('heroicon-o-envelope')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Tabs::make('')
                            ->schema([
                                Tab::make(__('Sender Information'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('subject')
                                                    ->columnSpanFull()
                                                    ->label(__('Subject')),
                                                TextEntry::make('from')
                                                    ->label(__('From'))
                                                    ->getStateUsing(fn(Mail $record) => self::formatMailState($record->from)),
                                                TextEntry::make('to')
                                                    ->label(__('Recipient'))
                                                    ->getStateUsing(fn(Mail $record) => self::formatMailState($record->to)),
                                                TextEntry::make('cc')
                                                    ->label(__('CC'))
                                                    ->default('-')
                                                    ->getStateUsing(fn(Mail $record) => self::formatMailState($record->cc ?? [])),
                                                TextEntry::make('bcc')
                                                    ->label(__('BCC'))
                                                    ->default('-')
                                                    ->getStateUsing(fn(Mail $record) => self::formatMailState($record->bcc ?? [])),
                                                TextEntry::make('reply_to')
                                                    ->default('-')
                                                    ->label(__('Reply To'))
                                                    ->getStateUsing(fn(Mail $record) => self::formatMailState($record->reply_to ?? [])),
                                            ]),
                                    ]),
                                Tab::make(__('Statistics'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('opens')
                                                    ->label(__('Opens')),
                                                TextEntry::make('clicks')
                                                    ->label(__('Clicks')),
                                                TextEntry::make('sent_at')
                                                    ->label(__('Sent At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('resent_at')
                                                    ->label(__('Resent At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('delivered_at')
                                                    ->label(__('Delivered At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('last_opened_at')
                                                    ->label(__('Last Opened At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('last_clicked_at')
                                                    ->label(__('Last Clicked At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('complained_at')
                                                    ->label(__('Complained At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('soft_bounced_at')
                                                    ->label(__('Soft Bounced At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                                TextEntry::make('hard_bounced_at')
                                                    ->label(__('Hard Bounced At'))
                                                    ->default(__('Never'))
                                                    ->formatStateUsing(function ($state) {
                                                        return $state === __('Never') ? $state : Carbon::parse($state)->format('d-m-Y H:i');
                                                    }),
                                            ]),
                                    ]),
                                Tab::make(__('Events'))
                                    ->schema([
                                        RepeatableEntry::make('events')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextEntry::make('type')
                                                    ->label(__('Type'))
                                                    ->badge()
                                                    ->url(fn(MailEvent $record) => route('filament.' . filament()->getCurrentPanel()?->getId() . '.resources.mails.events.view', [
                                                        'record' => $record,
                                                        'tenant' => filament()->getTenant()?->id,
                                                    ]))
                                                    ->color(fn(EventType $state): string => match ($state) {
                                                        EventType::DELIVERED => 'success',
                                                        EventType::CLICKED => 'clicked',
                                                        EventType::OPENED => 'info',
                                                        EventType::SOFT_BOUNCED => 'danger',
                                                        EventType::HARD_BOUNCED => 'danger',
                                                        EventType::COMPLAINED => 'danger',
                                                        EventType::UNSUBSCRIBED => 'danger',
                                                        EventType::ACCEPTED => 'success',
                                                    })
                                                    ->formatStateUsing(function (EventType $state) {
                                                        return ucfirst($state->value);
                                                    }),
                                                TextEntry::make('occurred_at')
                                                    ->url(fn(MailEvent $record) => route('filament.' . filament()->getCurrentPanel()?->getId() . '.resources.mails.events.view', [
                                                        'record' => $record,
                                                        'tenant' => filament()->getTenant()?->id,
                                                    ]))
                                                    ->since()
                                                    ->dateTimeTooltip('d-m-Y H:i')
                                                    ->label(__('Occurred At')),
                                            ])
                                            ->columns(2),
                                    ]),
                            ]),

                    ]),
                Section::make('Content')
                    ->icon('heroicon-o-document')
                    ->collapsible()
                    ->compact()
                    ->schema([
                        Tabs::make('Content')
                            ->label(__('Content'))
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'w-full max-w-full'])
                            ->tabs([
                                Tab::make('Preview')
                                    ->extraAttributes(['class' => 'w-full max-w-full'])
                                    ->schema([
                                        TextEntry::make('html')
                                            ->hiddenLabel()
                                            ->label(__('HTML Content'))
                                            ->extraAttributes(['class' => 'overflow-x-auto'])
                                            ->formatStateUsing(fn(string $state, Mail $record): View => view(
                                                'filament-mails::mails.preview',
                                                ['html' => $state, 'mail' => $record],
                                            )),
                                    ]),
                                Tab::make('HTML')
                                    ->schema([
                                        TextEntry::make('html')
                                            ->hiddenLabel()
                                            ->extraAttributes(['class' => 'overflow-x-auto'])
                                            ->formatStateUsing(fn(string $state, Mail $record): View => view(
                                                'filament-mails::mails.html',
                                                ['html' => $state, 'mail' => $record],
                                            ))
                                            ->copyable()
                                            ->copyMessage('Copied!')
                                            ->copyMessageDuration(1500)
                                            ->label(__('HTML Content'))
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Text')
                                    ->schema([
                                        TextEntry::make('text')
                                            ->hiddenLabel()
                                            ->copyable()
                                            ->copyMessage('Copied!')
                                            ->copyMessageDuration(1500)
                                            ->label(__('Text Content'))
                                            ->columnSpanFull(),
                                    ]),
                            ])->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Attachments')
                    ->icon('heroicon-o-paper-clip')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('attachments')
                            ->hiddenLabel()
                            ->label(__('Attachments'))
                            ->visible(fn(Mail $record) => $record->attachments->count() == 0)
                            ->default(__('Email has no attachments')),
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->label(__('Attachments'))
                            ->visible(fn(Mail $record) => $record->attachments->count() > 0)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('filename')
                                            ->label(__('Name')),
                                        TextEntry::make('size')
                                            ->label(__('Size')),
                                        TextEntry::make('mime')
                                            ->label(__('Mime Type')),
                                        ViewEntry::make('uuid')
                                            ->label(__('Download'))
                                            ->getStateUsing(fn($record) => $record)
                                            ->view('filament-mails::mails.download'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->paginated([50, 100, 'all'])
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        __('Soft Bounced') => 'warning',
                        __('Hard Bounced') => 'danger',
                        __('Complained') => 'danger',
                        __('Clicked') => 'clicked',
                        __('Opened') => 'info',
                        __('Delivered') => 'success',
                        __('Sent') => 'info',
                        __('Resent') => 'info',
                        __('Unsent') => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->limit(35)
                    ->sortable()
                    ->searchable(['subject', 'html', 'text']),
                Tables\Columns\IconColumn::make('attachments')
                    ->label('')
                    ->alignLeft()
                    ->getStateUsing(fn(Mail $record) => $record->attachments->count() > 0)
                    ->icon(fn(string $state): string => $state ? 'heroicon-o-paper-clip' : ''),
                Tables\Columns\TextColumn::make('to')
                    ->label(__('Recipient'))
                    ->limit(50)
                    ->getStateUsing(fn(Mail $record) => self::formatMailState(emails: $record->to, mailOnly: true))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('opens')
                    ->label(__('Opens'))
                    ->tooltip(fn(Mail $record) => __('Last opened at :date', ['date' => $record->last_opened_at?->format('d-m-Y H:i')]))
                    ->sortable(),
                Tables\Columns\TextColumn::make('clicks')
                    ->label(__('Clicks'))
                    ->tooltip(fn(Mail $record) => __('Last clicked at :date', ['date' => $record->last_clicked_at?->format('d-m-Y H:i')]))
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label(__('Sent At'))
                    ->dateTime('d-m-Y H:i')
                    ->since()
                    ->tooltip(fn(Mail $record) => $record->sent_at?->format('d-m-Y H:i'))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->label(__('View'))
                    ->hiddenLabel()
                    ->tooltip(__('View')),
                Action::make('resend')
                    ->label(__('Resend'))
                    ->icon('heroicon-o-arrow-uturn-right')
                    ->requiresConfirmation()
                    ->modalDescription(__('Are you sure you want to resend this mail?'))
                    ->hiddenLabel()
                    ->tooltip(__('Resend'))
                    ->form([
                        TextInput::make('to')
                            ->label(__('Recipient'))
                            ->helperText(__('You can add multiple email addresses separated by commas.'))
                            ->required(),
                        TextInput::make('cc')
                            ->label(__('CC')),
                        TextInput::make('bcc')
                            ->label(__('BCC')),
                    ])
                    ->fillForm(function (Mail $record) {
                        // Get all to, cc and bcc from the record. The values are in a json field. The keys are the email addresses.
                        $to = json_decode($record->to, true) ?? [];
                        $cc = json_decode($record->cc, true) ?? [];
                        $bcc = json_decode($record->bcc, true) ?? [];

                        return [
                            'to' => implode(', ', array_keys($to)),
                            'cc' => implode(', ', array_keys($cc)),
                            'bcc' => implode(', ', array_keys($bcc)),
                        ];
                    })
                    ->action(function (Mail $record, array $data) {
                        $to = explode(',', $data['to']);
                        $cc = explode(',', $data['cc']);
                        $bcc = explode(',', $data['bcc']);

                        (new ResendMail)->handle($record, $to, $cc, $bcc);

                        Notification::make()
                            ->title(__('Mail will be resent in the background'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resend')
                        ->label(__('Resend'))
                        ->icon('heroicon-o-arrow-uturn-right')
                        ->requiresConfirmation()
                        ->modalDescription(__('Are you sure you want to resend the selected mails?'))
                        ->hiddenLabel()
                        ->tooltip(__('Resend'))
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $record) {
                                $to = json_decode($record->to, true) ?? [];
                                $cc = json_decode($record->cc, true) ?? [];
                                $bcc = json_decode($record->bcc, true) ?? [];
                                (new ResendMail)->handle($record, $to, $cc, $bcc);
                            }

                            Notification::make()
                                ->title(__('Mails will be resent in the background'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMails::route('/'),
            'view' => ViewMail::route('/{record}/view'),
        ];
    }

    private static function formatMailState(array $emails, bool $mailOnly = false): string
    {
        return collect($emails)
            ->mapWithKeys(fn($value, $key) => [$key => $value ?? $key])
            ->map(fn($value, $key) => $mailOnly ? $key : ($value === null ? $key : "$value <$key>"))
            ->implode(', ');
    }

    public static function getWidgets(): array
    {
        return [
            MailStatsWidget::class,
        ];
    }
}
