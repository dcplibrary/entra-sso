<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-purple-700 min-h-screen py-10 px-5">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Welcome, {{ $user->name }}</h1>
                    <p class="text-gray-600 text-sm mt-1">Entra SSO Dashboard</p>
                </div>
                <form action="{{ route('entra.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-all">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- User Information -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                </svg>
                User Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                    <label class="text-xs font-semibold text-gray-600 uppercase block mb-1">Name</label>
                    <div class="text-sm text-gray-900 font-medium">{{ $user->name }}</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                    <label class="text-xs font-semibold text-gray-600 uppercase block mb-1">Email</label>
                    <div class="text-sm text-gray-900 font-medium">{{ $user->email }}</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                    <label class="text-xs font-semibold text-gray-600 uppercase block mb-1">Entra ID</label>
                    <div class="text-sm text-gray-900 font-medium">{{ $user->entra_id ?? 'N/A' }}</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                    <label class="text-xs font-semibold text-gray-600 uppercase block mb-1">Role</label>
                    <div class="text-sm text-gray-900 font-medium">
                        <span class="inline-block px-3 py-1 bg-green-500 text-white rounded-full text-xs font-semibold">
                            {{ $role }}
                        </span>
                    </div>
                </div>
            </div>

            @if(count($customClaims) > 0)
                <h3 class="text-base font-semibold text-gray-900 mt-6 mb-3">Custom Claims from Azure AD</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($customClaims as $claim => $value)
                        <div class="p-3 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                            <label class="text-xs font-semibold text-gray-600 uppercase block mb-1">{{ $claim }}</label>
                            <div class="text-sm text-gray-900 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Groups and Roles -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
                Azure AD Groups
            </h2>

            @if(count($groups) > 0)
                <div class="mb-4">
                    @foreach($groups as $group)
                        <span class="inline-block px-3 py-1 bg-amber-500 text-white rounded-full text-xs font-semibold mr-2 mb-2">
                            {{ $group }}
                        </span>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-600 py-6 italic">No Azure AD groups assigned</div>
            @endif

            <h3 class="text-base font-semibold text-gray-900 mt-6 mb-3">Group to Role Mapping</h3>
            @if(count($groupRoleMappings) > 0)
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-xs overflow-x-auto mt-3">
@foreach($groupRoleMappings as $group => $mappedRole)
"{{ $group }}" => "{{ $mappedRole }}"
@endforeach
                </div>
                <div class="mt-2 p-3 bg-amber-50 border-l-4 border-amber-500 rounded text-sm">
                    <strong class="text-amber-900">Example:</strong> Configure in .env: <code class="bg-amber-100 px-1 py-0.5 rounded text-xs">ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer"</code>
                </div>
            @else
                <div class="text-center text-gray-600 py-6 italic">No group role mappings configured</div>
                <div class="mt-2 p-3 bg-amber-50 border-l-4 border-amber-500 rounded text-sm">
                    <strong class="text-amber-900">Tip:</strong> Configure group-to-role mappings in your .env file using <code class="bg-amber-100 px-1 py-0.5 rounded text-xs">ENTRA_GROUP_ROLES="Group Name:role,Another Group:role2"</code>
                </div>
            @endif
        </div>

        <!-- Routes and Middleware -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                Available Routes
            </h2>

            @foreach($routes as $route)
                <div class="p-3 bg-gray-50 rounded-lg mb-2">
                    <div class="font-semibold text-gray-900 text-sm">{{ $route['name'] }}</div>
                    <div class="text-gray-600 text-sm mt-1">
                        @foreach($route['methods'] as $method)
                            @if($method !== 'HEAD')
                                <span class="inline-block px-2 py-0.5 {{ $method === 'POST' ? 'bg-green-500' : 'bg-blue-500' }} text-white rounded text-xs font-semibold mr-2">
                                    {{ $method }}
                                </span>
                            @endif
                        @endforeach
                        {{ $route['uri'] }}
                    </div>
                </div>
            @endforeach

            <h3 class="text-base font-semibold text-gray-900 mt-6 mb-3">Protecting Routes with Middleware</h3>
            <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-xs overflow-x-auto">
<span class="text-gray-500">// Protect by role</span>
Route::middleware(<span class="text-green-400">'entra.role:admin'</span>)->group(<span class="text-purple-400">function</span> () {
    Route::get(<span class="text-green-400">'/admin'</span>, [AdminController::<span class="text-purple-400">class</span>, <span class="text-green-400">'index'</span>]);
});

<span class="text-gray-500">// Protect by Azure AD group</span>
Route::middleware(<span class="text-green-400">'entra.group:IT Admins,Developers'</span>)->group(<span class="text-purple-400">function</span> () {
    Route::get(<span class="text-green-400">'/tools'</span>, [ToolsController::<span class="text-purple-400">class</span>, <span class="text-green-400">'index'</span>]);
});

<span class="text-gray-500">// Multiple middleware</span>
Route::middleware([<span class="text-green-400">'auth'</span>, <span class="text-green-400">'entra.role:manager'</span>])->group(<span class="text-purple-400">function</span> () {
    Route::get(<span class="text-green-400">'/reports'</span>, [ReportController::<span class="text-purple-400">class</span>, <span class="text-green-400">'index'</span>]);
});
            </div>

            <h3 class="text-base font-semibold text-gray-900 mt-6 mb-3">Using Helper Methods</h3>
            <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-xs overflow-x-auto">
<span class="text-gray-500">// Check user role</span>
@<span class="text-purple-400">if</span>(auth()->user()->hasRole(<span class="text-green-400">'admin'</span>))
    <span class="text-gray-500">// Show admin content</span>
@<span class="text-purple-400">endif</span>

<span class="text-gray-500">// Check Azure AD group membership</span>
@<span class="text-purple-400">if</span>(auth()->user()->inGroup(<span class="text-green-400">'Developers'</span>))
    <span class="text-gray-500">// Show developer tools</span>
@<span class="text-purple-400">endif</span>

<span class="text-gray-500">// Get custom claims</span>
<span class="text-blue-400">$department</span> = auth()->user()->getCustomClaim(<span class="text-green-400">'department'</span>);
            </div>
        </div>

        <!-- Documentation -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Next Steps
            </h2>
            <p class="text-gray-600 mb-4">
                This is a placeholder dashboard provided by the Entra SSO package.
                You can customize this view or redirect users to your own dashboard after login.
            </p>
            <a href="https://github.com/dcplibrary/entra-sso" target="_blank" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-all hover:-translate-y-0.5">
                View Documentation on GitHub →
            </a>
        </div>
    </div>
</body>
</html>
