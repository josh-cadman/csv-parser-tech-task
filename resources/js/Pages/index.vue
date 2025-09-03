<template>
    <div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Upload CSV File</h2>

        <form @submit.prevent="submitFile" class="space-y-4">
            <div>
                <label for="csv-file" class="block text-sm font-medium text-gray-700 mb-2">
                    Select CSV File
                </label>
                <input id="csv-file" type="file" ref="fileInput" accept=".csv" @change="handleFileChange"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    required />
                <p class="text-xs text-gray-500 mt-1">Only CSV files are accepted</p>
            </div>

            <!-- Error message -->
            <div v-if="form.errors.file" class="text-red-600 text-sm">
                {{ form.errors.file }}
            </div>

            <!-- Submit button -->
            <button type="submit" :disabled="form.processing || !selectedFile"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="form.processing">Uploading...</span>
                <span v-else>Upload CSV</span>
            </button>
        </form>

        <!-- Show output after the CSV has been parsed -->
        <div v-if="page.props.output && page.props.output.length > 0">
            <ul>
                <li v-for="(output, index) in page.props.output" :key="index">
                    <ul>
                        <li v-if="output.title">Title: {{ output.title }}</li>
                        <li v-if="output.first_name">First Name: {{ output.first_name }}</li>
                        <li v-if="output.initial">Initial: {{ output.initial }}</li>
                        <li v-if="output.last_name">Last Name: {{ output.last_name }}</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const fileInput = ref(null)
const selectedFile = ref(null)

const page = usePage()

const form = useForm({
    file: null
})

// Handle the file change by checking it is a CSV and store in the Inertia form object
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

// Submit the form
const submitFile = () => {
    if (!selectedFile.value) return

    // Submit the file using a post request with Inertia
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
</script>