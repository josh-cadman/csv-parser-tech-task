<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Homeowners</h1>
            <p class="text-gray-600 mt-2">Manage and view imported homeowner data</p>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="$page.props.success" class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ $page.props.success }}</p>
                    <p v-if="$page.props.totalCreated" class="text-sm text-green-700 mt-1">
                        Total records created: {{ $page.props.totalCreated }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="$page.props.error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ $page.props.error }}</p>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="mb-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Upload CSV File</h2>

            <form @submit.prevent="submitFile" class="space-y-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            id="csv-file"
                            type="file"
                            ref="fileInput"
                            accept=".csv"
                            @change="handleFileChange"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            required
                        />
                        <p class="text-xs text-gray-500 mt-1">Only CSV files are accepted</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !selectedFile"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                    >
                        <span v-if="form.processing">Uploading...</span>
                        <span v-else>Upload CSV</span>
                    </button>
                </div>

                <div v-if="form.errors.file" class="text-red-600 text-sm">
                    {{ form.errors.file }}
                </div>
            </form>
        </div>

        <!-- Search and Filters -->
        <div class="mb-6 bg-white rounded-lg shadow-md p-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input
                        v-model="searchForm.search"
                        type="text"
                        placeholder="Search by name..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        @input="debounceSearch"
                    />
                </div>
                <button
                    @click="clearSearch"
                    v-if="searchForm.search"
                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Homeowners List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div v-if="homeowners.data && homeowners.data.length > 0">
                <!-- Table Header -->
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Homeowners
                        <span class="text-sm text-gray-500 font-normal">
                            ({{ homeowners.total }} total)
                        </span>
                    </h3>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Title
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    First Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Initial
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="homeowner in homeowners.data" :key="homeowner.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ homeowner.full_name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ homeowner.title || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ homeowner.first_name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ homeowner.initial || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ homeowner.last_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(homeowner.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link
                            v-if="homeowners.prev_page_url"
                            :href="homeowners.prev_page_url"
                            preserve-state
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="homeowners.next_page_url"
                            :href="homeowners.next_page_url"
                            preserve-state
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>

                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ homeowners.from || 0 }}</span>
                                to
                                <span class="font-medium">{{ homeowners.to || 0 }}</span>
                                of
                                <span class="font-medium">{{ homeowners.total }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <!-- Previous -->
                                <Link
                                    v-if="homeowners.prev_page_url"
                                    :href="homeowners.prev_page_url"
                                    preserve-state
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </Link>

                                <!-- Page Numbers -->
                                <template v-for="page in paginationLinks" :key="page.label">
                                    <Link
                                        v-if="page.url"
                                        :href="page.url"
                                        preserve-state
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            page.active
                                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                        ]"
                                        v-html="page.label"
                                    >
                                    </Link>
                                    <span
                                        v-else
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                        v-html="page.label"
                                    >
                                    </span>
                                </template>

                                <!-- Next -->
                                <Link
                                    v-if="homeowners.next_page_url"
                                    :href="homeowners.next_page_url"
                                    preserve-state
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </Link>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No homeowners found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ searchForm.search ? 'Try adjusting your search terms.' : 'Upload a CSV file to get started.' }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'

// Props
const props = defineProps({
    homeowners: {
        type: Object,
        default: () => ({ data: [], total: 0 })
    },
    searchTerm: {
        type: String,
        default: ''
    }
})

// File upload logic
const fileInput = ref(null)
const selectedFile = ref(null)

const form = useForm({
    file: null
})

const handleFileChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        if (!file.type.includes('csv') && !file.name.endsWith('.csv')) {
            alert('Please select a CSV file')
            event.target.value = ''
            return
        }

        selectedFile.value = file
        form.file = file
    }
}

const submitFile = () => {
    if (!selectedFile.value) return

    form.post('/', {
        onSuccess: () => {
            selectedFile.value = null
            fileInput.value.value = ''
            form.reset()
        },
        onError: (errors) => {
            console.log('Upload errors:', errors)
        }
    })
}

// Search logic
const searchForm = useForm({
    search: props.searchTerm || ''
})

let searchTimeout = null

const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        performSearch()
    }, 300)
}

const performSearch = () => {
    router.get('/', { search: searchForm.search }, {
        preserveState: true,
        replace: true
    })
}

const clearSearch = () => {
    searchForm.search = ''
    performSearch()
}

// Pagination
const paginationLinks = computed(() => {
    if (!props.homeowners.links) return []

    // Remove first and last (prev/next) links as we handle them separately
    return props.homeowners.links.slice(1, -1)
})

// Utility functions
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>