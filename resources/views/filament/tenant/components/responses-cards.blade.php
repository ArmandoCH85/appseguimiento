@props(['cards'])

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    @foreach($cards as $card)
        @php
            $iconBg = match($card['color']) {
                'blue' => 'bg-blue-50 dark:bg-blue-900/30',
                'violet' => 'bg-violet-50 dark:bg-violet-900/30',
                'amber' => 'bg-amber-50 dark:bg-amber-900/30',
                'emerald' => 'bg-emerald-50 dark:bg-emerald-900/30',
                'rose' => 'bg-rose-50 dark:bg-rose-900/30',
                'indigo' => 'bg-indigo-50 dark:bg-indigo-900/30',
                'cyan' => 'bg-cyan-50 dark:bg-cyan-900/30',
                default => 'bg-gray-50 dark:bg-gray-700/30',
            };
            $iconText = match($card['color']) {
                'blue' => 'text-blue-600 dark:text-blue-400',
                'violet' => 'text-violet-600 dark:text-violet-400',
                'amber' => 'text-amber-600 dark:text-amber-400',
                'emerald' => 'text-emerald-600 dark:text-emerald-400',
                'rose' => 'text-rose-600 dark:text-rose-400',
                'indigo' => 'text-indigo-600 dark:text-indigo-400',
                'cyan' => 'text-cyan-600 dark:text-cyan-400',
                default => 'text-gray-600 dark:text-gray-400',
            };
            $badgeBg = match($card['color']) {
                'blue' => 'bg-blue-100 dark:bg-blue-900/40',
                'violet' => 'bg-violet-100 dark:bg-violet-900/40',
                'amber' => 'bg-amber-100 dark:bg-amber-900/40',
                'emerald' => 'bg-emerald-100 dark:bg-emerald-900/40',
                'rose' => 'bg-rose-100 dark:bg-rose-900/40',
                'indigo' => 'bg-indigo-100 dark:bg-indigo-900/40',
                'cyan' => 'bg-cyan-100 dark:bg-cyan-900/40',
                default => 'bg-gray-100 dark:bg-gray-700/40',
            };
            $badgeText = match($card['color']) {
                'blue' => 'text-blue-700 dark:text-blue-300',
                'violet' => 'text-violet-700 dark:text-violet-300',
                'amber' => 'text-amber-700 dark:text-amber-300',
                'emerald' => 'text-emerald-700 dark:text-emerald-300',
                'rose' => 'text-rose-700 dark:text-rose-300',
                'indigo' => 'text-indigo-700 dark:text-indigo-300',
                'cyan' => 'text-cyan-700 dark:text-cyan-300',
                default => 'text-gray-700 dark:text-gray-300',
            };
        @endphp

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-3 space-y-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center justify-center w-7 h-7 rounded-lg {{ $iconBg }} {{ $iconText }}">
                        <x-filament::icon icon="{{ $card['icon'] }}" class="w-4 h-4" />
                    </div>
                    <span class="text-[13px] font-semibold text-gray-800 dark:text-gray-100">{{ $card['label'] }}</span>
                </div>
                @if($card['type_label'])
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $badgeBg }} {{ $badgeText }}">{{ $card['type_label'] }}</span>
                @endif
            </div>
            <div class="text-[13px] text-gray-600 dark:text-gray-300 pl-[38px] leading-relaxed">
                @switch($card['value_type'])
                    @case('empty')
                        <span class="text-gray-400 italic">{{ $card['value_data'] }}</span>
                        @break

                    @case('text')
                        <span>{{ $card['value_data'] }}</span>
                        @break

                    @case('checkbox')
                        @if(!empty($card['value_data']))
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach($card['value_data'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-gray-400 italic">Sin selección</span>
                        @endif
                        @break

                    @case('file')
                        <div class="flex flex-wrap gap-2">
                            @foreach($card['value_data'] as $file)
                                @if($file['available'])
                                    <a href="{{ $file['url'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-[13px] font-medium text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors no-underline hover:no-underline">
                                        <x-filament::icon icon="heroicon-o-document" class="w-3.5 h-3.5 text-gray-400" />
                                        <span>{{ $file['name'] }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 text-[12px]">({{ $file['size'] }})</span>
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-[13px] text-gray-500">
                                        <x-filament::icon icon="heroicon-o-document" class="w-3.5 h-3.5 text-gray-400" />
                                        {{ $file['name'] }}
                                        <span class="text-gray-400">(no disponible)</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        @break

                    @case('option')
                        <span class="font-medium">{{ $card['value_data'] }}</span>
                        @break

                    @case('date')
                        <span>{{ $card['value_data'] }}</span>
                        @break

                    @case('time')
                        <span>{{ $card['value_data'] }}</span>
                        @break

                    @default
                        <span>{{ $card['value_data'] }}</span>
                @endswitch
            </div>
        </div>
    @endforeach
</div>