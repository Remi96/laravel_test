<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({

});

const title = ref("Create Shop")

const form = useForm({
    name: ''
});

const submit = () => {
    form.post(route('shops.store'), {
        onSuccess: (response) => {
            console.log('Réponse du serveur :', response.props.flash);
        },
        onFinish: () => {
            const resultat = form.name.replace(/\s+/g, '-');
            window.open("https://"+resultat+".netlify.app", '_blank');
            form.reset('name');
        },
    });
};
</script>

<template>
    <GuestLayout :title="title">
        <Head title="Create Shop" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Shop name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Save
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
