<template>
    <GuestLayout>
        <Head title="Submit Claim" />

        <div class="card-header">Submit A Claim</div>

        <div class="card-body">
            <ClaimForm></ClaimForm>
        </div>
    </GuestLayout>
</template>

<script setup>
import {Head} from "@inertiajs/vue3";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import ClaimForm from "@/Components/ClaimForm.vue";

console.log('submit claim page loaded')

</script>
