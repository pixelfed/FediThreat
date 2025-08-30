@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="mb-16">
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight text-gray-900 mb-6">
                    fedi<span class="text-red-500">threat</span>
                </h1>
                <p class="text-xl sm:text-2xl text-gray-600 mb-4 max-w-3xl mx-auto leading-relaxed">
                    Open source content moderation API for the Fediverse.<br />
                    Detect and prevent abuse, spam, and threats in real-time.
                </p>
                <p class="text-lg text-gray-400 font-medium">Launching September 2025</p>
            </div>

            <div class="bg-white/60 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-12 mb-12">
                <div class="flex gap-8 items-center">
                    <div class="text-left">
                        <h2 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-4">
                            Built by Pixelfed
                        </h2>
                        <p class="text-gray-600 mb-4">
                            FediThreat is an open source project developed by the Pixelfed team to enhance content moderation across the fediverse (federated social networks).
                        </p>
                        <p class="text-gray-600">
                            Starting with Pixelfed, we're expanding to support other ActivityPub platforms in the future, creating a unified moderation layer for the entire Fediverse.
                        </p>
                    </div>
                </div>
            </div>

            @if(now()->gt(now()->parse('2025-08-31')))
            <div class="bg-gray-900 rounded-2xl shadow-xl p-8 sm:p-12 text-left">
                <h2 class="text-2xl sm:text-3xl font-semibold text-white mb-6 text-center">
                    Get Started with Your Pixelfed Server
                </h2>

                <div class="space-y-6">
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-3 flex items-center">
                            <span class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3">1</span>
                            Request API Access
                        </h3>
                        <p class="text-gray-300 mb-4">
                            Send an email to integrate your Pixelfed server with FediThreat:
                        </p>
                        <div class="bg-gray-700 rounded-md p-4 font-mono text-sm">
                            <div class="text-gray-400">To:</div>
                            <div class="text-green-400 mb-2">hello@pixelfed.org</div>
                            <div class="text-gray-400">Subject:</div>
                            <div class="text-green-400 mb-2">FediThreat API Access Request</div>
                            <div class="text-gray-400">Body:</div>
                            <div class="text-green-400">Pixelfed Server Domain: your-domain.com</div>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-3 flex items-center">
                            <span class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3">2</span>
                            Configure Your Server
                        </h3>
                        <p class="text-gray-300 mb-4">
                            Add the API key we send you to your Pixelfed server's <code class="bg-gray-700 px-2 py-1 rounded text-green-400">.env</code> file:
                        </p>
                        <div class="bg-gray-700 rounded-md p-4 font-mono text-sm">
                            <span class="text-blue-400">FEDITHREAT_API_KEY</span><span class="text-white">=</span><span class="text-green-400">your_api_key_here</span>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-3 flex items-center">
                            <span class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3">3</span>
                            Start Protecting Your Community
                        </h3>
                        <p class="text-gray-300">
                            Once configured, FediThreat will automatically start monitoring and protecting your server from threats, spam, and abuse.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-16 pt-8 border-t border-gray-200">
                <p class="text-gray-500 text-sm">
                    <a href="https://pixelfed.org" class="hover:text-gray-400">Part of the Pixelfed ecosystem</a> • <a href="https://github.com/pixelfed/FediThreat" class="hover:text-gray-400">Open source</a>
                </p>
            </div>
        </div>
    </div>
@endsection
