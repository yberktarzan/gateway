<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📊 Log Monitor - Gateway</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        .log-level-error { @apply bg-red-50 border-l-4 border-red-500; }
        .log-level-warning { @apply bg-yellow-50 border-l-4 border-yellow-500; }
        .log-level-info { @apply bg-blue-50 border-l-4 border-blue-500; }
        .log-level-debug { @apply bg-gray-50 border-l-4 border-gray-500; }
        
        .log-badge-error { @apply bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium; }
        .log-badge-warning { @apply bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-medium; }
        .log-badge-info { @apply bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium; }
        .log-badge-debug { @apply bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium; }
        
        .status-2xx { @apply bg-green-100 text-green-800; }
        .status-3xx { @apply bg-blue-100 text-blue-800; }
        .status-4xx { @apply bg-yellow-100 text-yellow-800; }
        .status-5xx { @apply bg-red-100 text-red-800; }
        
        .animate-pulse-soft {
            animation: pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .log-entry {
            transition: all 0.3s ease;
        }
        
        .log-entry:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100" x-data="logMonitor()">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-gray-900">📊 Log Monitor</h1>
                        <div class="flex items-center space-x-2">
                            <div :class="isConnected ? 'bg-green-500' : 'bg-red-500'" 
                                 class="w-3 h-3 rounded-full animate-pulse-soft"></div>
                            <span class="text-sm text-gray-600" x-text="isConnected ? 'Canlı' : 'Bağlantı Yok'"></span>
                            <span class="text-xs text-gray-500" x-text="lastUpdate"></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Stats -->
                        <div class="flex items-center space-x-4 text-sm">
                            <div class="bg-blue-100 px-3 py-1 rounded-full">
                                <span x-text="logs.length"></span> logs
                            </div>
                            <div class="bg-red-100 px-3 py-1 rounded-full" x-show="errorCount > 0">
                                <span x-text="errorCount"></span> errors
                            </div>
                        </div>
                        
                        <!-- Auto-refresh toggle -->
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" x-model="autoRefresh" class="rounded">
                            <span class="text-sm text-gray-700">Auto-refresh</span>
                        </label>
                        
                        <!-- Manual refresh button -->
                        <button @click="fetchLogs()" 
                                :disabled="loading"
                                class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <span x-show="!loading">🔄 Refresh</span>
                            <span x-show="loading" class="animate-spin">⏳</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border-b shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Level Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Level</label>
                        <select x-model="filters.level" @change="fetchLogs()" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="all">All Levels</option>
                            <template x-for="level in availableFilters.levels" :key="level">
                                <option :value="level" x-text="level.toUpperCase()"></option>
                            </template>
                        </select>
                    </div>
                    
                    <!-- Domain Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Domain</label>
                        <select x-model="filters.domain" @change="fetchLogs()" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="all">All Domains</option>
                            <template x-for="domain in availableFilters.domains" :key="domain">
                                <option :value="domain" x-text="domain"></option>
                            </template>
                        </select>
                    </div>
                    
                    <!-- Action Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Action</label>
                        <select x-model="filters.action" @change="fetchLogs()" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="all">All Actions</option>
                            <template x-for="action in availableFilters.actions" :key="action">
                                <option :value="action" x-text="action"></option>
                            </template>
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="fetchLogs()" 
                               placeholder="Search logs..."
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    
                    <!-- Time Range -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Time Range</label>
                        <select x-model="timeRange" @change="updateTimeRange()" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="30m">Last 30 minutes</option>
                            <option value="1h">Last 1 hour</option>
                            <option value="3h">Last 3 hours</option>
                            <option value="6h">Last 6 hours</option>
                            <option value="24h">Last 24 hours</option>
                        </select>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex space-x-2">
                        <button @click="clearFilters()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                            Clear Filters
                        </button>
                        <button @click="onlyErrors()" 
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                            Only Errors
                        </button>
                        <button @click="createTestLog('info')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                            Create Test Log
                        </button>
                    </div>
                    
                    <div class="text-xs text-gray-500" x-show="fallbackMode">
                        ⚠️ Elasticsearch unavailable - showing local logs
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Container -->
        <div class="max-w-7xl mx-auto px-4 py-6">
            <!-- Loading State -->
            <div x-show="loading && logs.length === 0" class="text-center py-12">
                <div class="animate-spin text-4xl mb-4">⏳</div>
                <p class="text-gray-600">Loading logs...</p>
            </div>
            
            <!-- Empty State -->
            <div x-show="!loading && logs.length === 0" class="text-center py-12">
                <div class="text-6xl mb-4">📝</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No logs found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your filters or create a test log</p>
                <button @click="createTestLog('info')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Create Test Log
                </button>
            </div>
            
            <!-- Log Entries -->
            <div x-show="logs.length > 0" class="space-y-3">
                <template x-for="log in logs" :key="log.id">
                    <div class="log-entry bg-white rounded-lg shadow-sm border hover:shadow-md"
                         :class="'log-level-' + log.level">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <!-- Log Level Badge -->
                                    <span :class="'log-badge-' + log.level" x-text="log.level_name || log.level.toUpperCase()"></span>
                                    
                                    <!-- Domain & Action -->
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <span x-show="log.domain" x-text="log.domain_name || log.domain" class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs"></span>
                                        <span x-show="log.action" x-text="log.action_name || log.action" class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-xs"></span>
                                    </div>
                                    
                                    <!-- HTTP Status -->
                                    <span x-show="log.http && log.http.status" 
                                          :class="getStatusClass(log.http.status)"
                                          class="px-2 py-1 rounded text-xs font-medium"
                                          x-text="log.http.status"></span>
                                </div>
                                
                                <!-- Timestamp -->
                                <div class="text-sm text-gray-500" x-text="formatTimestamp(log.timestamp)"></div>
                            </div>
                            
                            <!-- Main Message -->
                            <div class="mb-3">
                                <p class="text-gray-900 font-medium" x-text="log.message"></p>
                            </div>
                            
                            <!-- HTTP Details -->
                            <div x-show="log.http && (log.http.method || log.http.path)" class="mb-3">
                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                    <span x-show="log.http.method" 
                                          class="font-mono bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs"
                                          x-text="log.http.method"></span>
                                    <span x-show="log.http.path" x-text="log.http.path" class="font-mono"></span>
                                    <span x-show="log.http.ip" class="text-gray-500" x-text="'(' + log.http.ip + ')'"></span>
                                </div>
                            </div>
                            
                            <!-- Expandable Context -->
                            <div x-show="log.context && Object.keys(log.context).length > 0" class="mt-3">
                                <button @click="log.expanded = !log.expanded" 
                                        class="text-sm text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                                    <span x-text="log.expanded ? 'Hide' : 'Show'"></span>
                                    <span>Context</span>
                                    <svg x-show="!log.expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <svg x-show="log.expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </button>
                                
                                <div x-show="log.expanded" x-collapse class="mt-2 bg-gray-50 rounded p-3">
                                    <pre class="text-xs text-gray-700 whitespace-pre-wrap" x-text="JSON.stringify(log.context, null, 2)"></pre>
                                </div>
                            </div>
                            
                            <!-- Exception Details -->
                            <div x-show="log.exception" class="mt-3 bg-red-50 border border-red-200 rounded p-3">
                                <div class="text-sm font-medium text-red-800 mb-1">Exception</div>
                                <div class="text-xs text-red-700">
                                    <div x-show="log.exception?.class" x-text="log.exception.class"></div>
                                    <div x-show="log.exception?.file" x-text="log.exception.file"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Load More -->
            <div x-show="logs.length >= 100" class="text-center mt-6">
                <button @click="loadMore()" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">
                    Load More Logs
                </button>
            </div>
        </div>
    </div>

    <script>
        function logMonitor() {
            return {
                // State
                logs: [],
                loading: false,
                isConnected: true,
                autoRefresh: true,
                lastUpdate: '',
                fallbackMode: false,
                errorCount: 0,
                timeRange: '30m',
                
                // Filters
                filters: {
                    level: 'all',
                    domain: 'all',
                    action: 'all',
                    search: '',
                    since: ''
                },
                
                availableFilters: {
                    levels: [],
                    domains: [],
                    actions: []
                },
                
                // Initialize
                init() {
                    this.updateTimeRange();
                    this.fetchFilters();
                    this.fetchLogs();
                    
                    // Auto-refresh interval
                    setInterval(() => {
                        if (this.autoRefresh) {
                            this.fetchLogs();
                        }
                    }, 5000);
                },
                
                // Fetch available filters
                async fetchFilters() {
                    try {
                        const response = await fetch('/log-monitor/filters');
                        const data = await response.json();
                        if (data.success) {
                            this.availableFilters = data.data;
                        }
                    } catch (error) {
                        console.error('Failed to fetch filters:', error);
                    }
                },
                
                // Fetch logs
                async fetchLogs() {
                    this.loading = true;
                    
                    try {
                        const params = new URLSearchParams({
                            level: this.filters.level,
                            domain: this.filters.domain,
                            action: this.filters.action,
                            search: this.filters.search,
                            since: this.filters.since,
                            limit: 100
                        });
                        
                        const response = await fetch(`/log-monitor/logs?${params}`);
                        const data = await response.json();
                        
                        if (data.success) {
                            this.logs = data.data.logs.map(log => ({ ...log, expanded: false }));
                            this.fallbackMode = data.data.fallback_mode || false;
                            this.errorCount = this.logs.filter(log => log.level === 'error').length;
                            this.isConnected = true;
                            this.lastUpdate = new Date().toLocaleTimeString('tr-TR');
                        } else {
                            this.isConnected = false;
                        }
                    } catch (error) {
                        console.error('Failed to fetch logs:', error);
                        this.isConnected = false;
                    } finally {
                        this.loading = false;
                    }
                },
                
                // Create test log
                async createTestLog(level = 'info') {
                    try {
                        const response = await fetch('/log-monitor/test', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ level })
                        });
                        
                        if (response.ok) {
                            // Refresh logs after creating test log
                            setTimeout(() => this.fetchLogs(), 1000);
                        }
                    } catch (error) {
                        console.error('Failed to create test log:', error);
                    }
                },
                
                // Update time range
                updateTimeRange() {
                    const now = new Date();
                    const ranges = {
                        '30m': 30 * 60 * 1000,
                        '1h': 60 * 60 * 1000,
                        '3h': 3 * 60 * 60 * 1000,
                        '6h': 6 * 60 * 60 * 1000,
                        '24h': 24 * 60 * 60 * 1000
                    };
                    
                    const since = new Date(now - ranges[this.timeRange]);
                    this.filters.since = since.toISOString();
                    this.fetchLogs();
                },
                
                // Clear all filters
                clearFilters() {
                    this.filters.level = 'all';
                    this.filters.domain = 'all';
                    this.filters.action = 'all';
                    this.filters.search = '';
                    this.fetchLogs();
                },
                
                // Show only errors
                onlyErrors() {
                    this.filters.level = 'error';
                    this.fetchLogs();
                },
                
                // Get CSS class for HTTP status
                getStatusClass(status) {
                    if (!status) return '';
                    
                    if (status >= 200 && status < 300) return 'status-2xx';
                    if (status >= 300 && status < 400) return 'status-3xx';
                    if (status >= 400 && status < 500) return 'status-4xx';
                    if (status >= 500) return 'status-5xx';
                    
                    return '';
                },
                
                // Format timestamp
                formatTimestamp(timestamp) {
                    return new Date(timestamp).toLocaleString('tr-TR', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }
            }
        }
    </script>
</body>
</html>
