<x-layouts.storefront>
    <div class="mx-auto max-w-4xl">
        <flux:heading size="xl" class="!font-bold mb-4">About Us</flux:heading>

        {{-- Hero image --}}
        <div class="mb-8 overflow-hidden rounded-2xl">
            <img
                src="{{ asset('images/coconut-bulk.png') }}"
                alt="{{ __('King coconut collection at Smart Agro') }}"
                class="h-56 w-full object-cover md:h-72"
            >
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-3">
                <flux:heading class="!font-semibold">Contact Details</flux:heading>
                <p><strong>Location:</strong> Sri Lanka, Thissa Road, Wadigala</p>
                <p><strong>Email:</strong> <a href="mailto:smartagro2025@gmail.com" class="text-emerald-600">smartagro2025@gmail.com</a></p>
                <p><strong>Phone:</strong> <a href="tel:0715795206" class="text-emerald-600">0715795206</a></p>

                <div class="mt-4">
                    <a href="mailto:smartagro2025@gmail.com?subject=Inquiry%20from%20website" class="inline-block">
                        <flux:button variant="outline">Send Inquiry</flux:button>
                    </a>
                </div>
            </div>

            <div>
                <flux:heading class="!font-semibold">Company History</flux:heading>
                <flux:text class="mt-2">Smart Agro was founded in 2025 as an agricultural company. From the beginning, our mission has been to preserve the high quality of Sri Lankan king coconut and supply it to both domestic and international markets.</flux:text>

                <flux:heading class="!font-semibold mt-4">King Coconut</flux:heading>
                <flux:text class="mt-2">King coconut is a premium coconut variety native to Sri Lanka, prized for its clean, refreshing taste and natural sweetness. We source coconuts from carefully selected farms and apply strict quality checks to ensure freshness and consistent flavor from harvest through packaging.</flux:text>
            </div>
        </div>

        {{-- Processing & Quality section with image --}}
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="overflow-hidden rounded-xl">
                <img
                    src="{{ asset('images/coconut-packaged.jpg') }}"
                    alt="{{ __('King coconuts in protective mesh packaging') }}"
                    class="size-full object-cover"
                    loading="lazy"
                >
            </div>
            <div>
                <flux:heading class="!font-semibold">Processing & Quality</flux:heading>
                <flux:text class="mt-2">Approximately 60% of our processing is performed manually. This hands-on approach allows for careful individual inspection at every step, reducing defects and ensuring higher overall product quality.</flux:text>

                <ul class="mt-3 list-disc ml-5 space-y-1 text-sm">
                    <li>Sourced from carefully selected local farms</li>
                    <li>Hand-inspected for maturity and taste</li>
                    <li>Washed, sorted, and safely packed using export-grade procedures</li>
                    <li>Packaged and labeled to meet international export standards</li>
                </ul>

                <flux:heading class="!font-semibold mt-4">Our Team</flux:heading>
                <flux:text class="mt-2">We currently employ 12 people across farming, processing, and management. Despite our small size, we prioritise responsibility and strict quality control at every stage.</flux:text>
            </div>
        </div>

        {{-- Delivery gallery --}}
        <div class="mt-8">
            <flux:heading class="!font-semibold mb-4">Our Packaging & Delivery</flux:heading>
            <div class="grid grid-cols-3 gap-4">
                <div class="overflow-hidden rounded-xl">
                    <img
                        src="{{ asset('images/coconut-raw.jpg') }}"
                        alt="{{ __('Fresh king coconuts sorted by quality') }}"
                        class="aspect-square w-full object-cover"
                        loading="lazy"
                    >
                </div>
                <div class="overflow-hidden rounded-xl">
                    <img
                        src="{{ asset('images/coconut-boxed.jpg') }}"
                        alt="{{ __('King coconuts wrapped for safe shipping') }}"
                        class="aspect-square w-full object-cover"
                        loading="lazy"
                    >
                </div>
                <div class="overflow-hidden rounded-xl">
                    <img
                        src="{{ asset('images/coconut-delivery.webp') }}"
                        alt="{{ __('King coconuts boxed and ready for delivery') }}"
                        class="aspect-square w-full object-cover"
                        loading="lazy"
                    >
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>

