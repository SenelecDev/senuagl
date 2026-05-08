<template>
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th v-for="col in columns" :key="col" class="border border-gray-300 px-4 py-2 text-left">{{ col }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in data" :key="index" class="hover:bg-gray-50">
                    <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="border border-gray-300 px-4 py-2" v-html="cell">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    columns: {
        type: Array,
        required: true
    },
    data: {
        type: Array,
        required: true
    }
})
</script>