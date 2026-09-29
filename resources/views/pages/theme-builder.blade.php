{{--
    Templates of the site, zone by zone. A header and a footer belong to the
    template, so they open it; a body belongs to a page, and for a template
    scoped to a model that page is created on the way to the editor.
--}}
<x-primix::pages.page :page="$this">
    <div class="tgx-theme-templates">
        @forelse ($this->templates() as $template)
            <section class="tgx-theme-template">
                <header class="tgx-theme-template-head">
                    <div>
                        <h2 class="tgx-theme-template-name">
                            {{ $template->name }}
                            @if ($template->is_global)
                                <span class="tgx-theme-badge">{{ __('Global') }}</span>
                            @endif
                        </h2>
                        <p class="tgx-theme-template-conditions">{{ $this->conditionsSummary($template) }}</p>
                    </div>
                    <a class="tgx-theme-link" href="{{ \Tagixo\Primix\Resources\LayoutResource::getUrl('edit', ['record' => $template->getKey()]) }}">
                        {{ __('Settings') }}
                    </a>
                </header>

                <div class="tgx-theme-zones">
                    @foreach ($this->zones($template) as $zone)
                        <article class="tgx-theme-zone {{ $zone['configured'] ? 'is-configured' : '' }}">
                            <h3 class="tgx-theme-zone-label">{{ $zone['label'] }}</h3>
                            <p class="tgx-theme-zone-state">
                                {{ $zone['configured'] ? __('Built') : __('Empty') }}
                            </p>
                            <p class="tgx-theme-zone-description">{{ $zone['description'] }}</p>
                            @if ($zone['url'])
                                <a class="tgx-theme-zone-action" href="{{ $zone['url'] }}">
                                    {{ $zone['configured'] ? __('Edit') : __('Build') }}
                                </a>
                            @else
                                <span class="tgx-theme-zone-action is-disabled">{{ __('Not here') }}</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="tgx-theme-empty">{{ __('No template yet. A page without one wears nothing.') }}</p>
        @endforelse
    </div>

    <style>
        .tgx-theme-templates { display: flex; flex-direction: column; gap: 1.25rem; }
        .tgx-theme-template { border: 1px solid var(--p-content-border-color, #e5e7eb); border-radius: .75rem; overflow: hidden; }
        .tgx-theme-template-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--p-content-border-color, #e5e7eb); }
        .tgx-theme-template-name { display: flex; align-items: center; gap: .5rem; margin: 0; font-size: 1rem; font-weight: 600; }
        .tgx-theme-badge { font-size: .7rem; font-weight: 500; padding: .1rem .45rem; border-radius: 999px; background: var(--p-primary-100, #e0e7ff); color: var(--p-primary-700, #4338ca); }
        .tgx-theme-template-conditions { margin: .25rem 0 0; font-size: .8rem; opacity: .7; }
        .tgx-theme-zones { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1px; background: var(--p-content-border-color, #e5e7eb); }
        .tgx-theme-zone { background: var(--p-content-background, #fff); padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: .25rem; }
        .tgx-theme-zone-label { margin: 0; font-size: .85rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; opacity: .6; }
        .tgx-theme-zone-state { margin: 0; font-weight: 600; }
        .tgx-theme-zone.is-configured .tgx-theme-zone-state { color: var(--p-green-600, #16a34a); }
        .tgx-theme-zone-description { margin: 0; font-size: .8rem; opacity: .7; }
        .tgx-theme-zone-action { margin-top: .5rem; align-self: flex-start; font-size: .85rem; font-weight: 500; }
        .tgx-theme-zone-action.is-disabled { opacity: .5; }
        .tgx-theme-empty { opacity: .7; }
    </style>
</x-primix::pages.page>
