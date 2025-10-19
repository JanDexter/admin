<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  defaultStatus: { type: String, default: 'active' },
});

const emit = defineEmits(['close', 'created']);

const form = useForm({
  name: '',
  company_name: '',
  email: '',
  phone: '',
  status: 'active',
});

watch(() => props.show, (val) => {
  if (val) {
    form.reset();
    form.status = props.defaultStatus || 'active';
  }
});

const submitting = computed(() => form.processing);

const submit = () => {
  form.transform((data) => ({ ...data, inline: true }));
  form.post(route('customers.store'), {
    preserveScroll: true,
    onSuccess: (page) => {
      const newCustomer = page?.props?.flash?.customer || null;
      emit('created', newCustomer);
      form.reset();
      form.transform((data) => data); // clear transform
    },
    onError: () => {
      form.transform((data) => data); // clear transform even on error
    }
  });
};
const onPhoneInput = (e) => {
  let v = e.target.value.replace(/\D/g, '');
  // Normalize 63xxxxxxxxxx -> 09xxxxxxxxx when pattern matches
  if (/^63(9\d{9})$/.test(v)) v = '0' + RegExp.$1;
  if (/^9\d{9}$/.test(v)) v = '0' + v; // 9xxxxxxxxx -> 09xxxxxxxxx
  if (v.length > 11) v = v.slice(0, 11);
  form.phone = v;
};

const phoneError = computed(() => {
  if (!form.phone) return '';
  return /^09\d{9}$/.test(form.phone) ? '' : 'Phone must start with 09 and be 11 digits.';
});

</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="submit">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Customer</h3>
            <div class="space-y-4">
              <div>
                <label for="customer_name" class="block text-sm font-medium text-gray-700">Name *</label>
                <input v-model="form.name" type="text" id="customer_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
              </div>
              <div>
                <label for="customer_company" class="block text-sm font-medium text-gray-700">Company Name</label>
                <input v-model="form.company_name" type="text" id="customer_company" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
              </div>
              <div>
                <label for="customer_email" class="block text-sm font-medium text-gray-700">Email *</label>
                <input v-model="form.email" type="email" id="customer_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
              </div>
              <div>
                <label for="customer_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                <input v-model="form.phone" @input="onPhoneInput" inputmode="numeric" pattern="09\\d{9}" maxlength="11" placeholder="09XXXXXXXXX" type="tel" id="customer_phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                <div v-if="phoneError || form.errors.phone" class="mt-1 text-sm" :class="phoneError ? 'text-red-600' : 'text-red-600'">
                  {{ form.errors.phone || phoneError }}
                </div>
              </div>
              <div>
                <label for="customer_status" class="block text-sm font-medium text-gray-700">Status</label>
                <select v-model="form.status" id="customer_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" :disabled="submitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
              <span v-if="submitting">Saving...</span>
              <span v-else>Save Customer</span>
            </button>
            <button type="button" @click="$emit('close')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
