<template>
    <div class="claim-form">
        <h2>Submit New Claim</h2>
        <form @submit.prevent="submitClaim">
            <div class="form-group">
                <label for="provider_name">Provider Name</label>
                <input
                    type="text"
                    id="provider_name"
                    v-model="form.provider_name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="insurer_code">Insurer Code</label>
                <input
                    type="text"
                    id="insurer_code"
                    v-model="form.insurer_code"
                    required
                    @input="searchInsurers"
                >
                <ul v-if="insurerSuggestions.length" class="suggestions">
                    <li
                        v-for="insurer in insurerSuggestions"
                        :key="insurer.code"
                        @click="selectInsurer(insurer)"
                    >
                        {{ insurer.code }} - {{ insurer.name }}
                    </li>
                </ul>
            </div>

            <div class="form-group">
                <label for="encounter_date">Encounter Date</label>
                <input
                    type="date"
                    id="encounter_date"
                    v-model="form.encounter_date"
                    required
                >
            </div>

            <div class="form-group">
                <label for="specialty">Specialty</label>
                <select id="specialty" v-model="form.specialty" required>
                    <option value="">Select Specialty</option>
                    <option v-for="specialty in specialties" :key="specialty" :value="specialty">
                        {{ specialty }}
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="priority_level">Priority Level</label>
                <select id="priority_level" v-model="form.priority_level" required>
                    <option value="">Select Priority</option>
                    <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>

            <div class="items-section">
                <h3>Claim Items</h3>
                <div v-for="(item, index) in form.items" :key="index" class="item-row">
                    <input
                        type="text"
                        v-model="item.name"
                        placeholder="Item name"
                        required
                    >
                    <input
                        type="number"
                        v-model.number="item.unit_price"
                        placeholder="Unit price"
                        min="0"
                        step="0.01"
                        required
                    >
                    <input
                        type="number"
                        v-model.number="item.quantity"
                        placeholder="Qty"
                        min="1"
                        required
                    >
                    <span class="subtotal">${{ (item.unit_price * item.quantity).toFixed(2) }}</span>
                    <button type="button" @click="removeItem(index)" class="remove-btn">×</button>
                </div>
                <button type="button" @click="addItem" class="add-item-btn">+ Add Item</button>
            </div>

            <div class="total-section">
                <label>Total Claim Amount:</label>
                <input
                    type="text"
                    :value="'$' + totalAmount.toFixed(2)"
                    readonly
                >
            </div>

            <button type="submit" :disabled="isSubmitting" class="submit-btn">
                {{ isSubmitting ? 'Submitting...' : 'Submit Claim' }}
            </button>

            <div v-if="successMessage" class="success-message">
                {{ successMessage }}
                <p>Batch ID: {{ batchIdentifier }}</p>
                <p>Estimated Processing Date: {{ processingDate }}</p>
            </div>

            <div v-if="errorMessage" class="error-message">
                {{ errorMessage }}
            </div>
        </form>
    </div>
</template>

<script>
import {router} from "@inertiajs/vue3";

export default {
    data() {
        return {
            form: {
                provider_name: '',
                insurer_code: '',
                encounter_date: '',
                specialty: '',
                priority_level: '',
                items: [
                    { name: '', unit_price: 1, quantity: 1 }
                ]
            },
            insurerSuggestions: [],
            specialties: [
                'Cardiology', 'Orthopedics', 'Neurology',
                'Dermatology', 'Pediatrics', 'Oncology'
            ],
            isSubmitting: false,
            successMessage: '',
            errorMessage: '',
            batchIdentifier: '',
            processingDate: ''
        }
    },
    computed: {
        totalAmount() {
            return this.form.items.reduce((total, item) => {
                return total + (item.unit_price * item.quantity);
            }, 0);
        }
    },
    methods: {
        addItem() {
            this.form.items.push({ name: '', unit_price: 0, quantity: 1 });
        },
        removeItem(index) {
            if (this.form.items.length > 1) {
                this.form.items.splice(index, 1);
            }
        },
        async searchInsurers() {
            if (this.form.insurer_code.length < 2) return;

            try {
                const response = await axios.get('/api/insurers', {
                    params: { search: this.form.insurer_code }
                });
                this.insurerSuggestions = response.data;
            } catch (error) {
                console.error('Error searching insurers:', error);
            }
        },
        selectInsurer(insurer) {
            this.form.insurer_code = insurer.code;
            this.insurerSuggestions = [];
        },
        async submitClaim() {
            this.isSubmitting = true;
            this.errorMessage = '';
            this.successMessage = '';

            try {
                const response = await axios.post('api/v1/claims', this.form);

                this.successMessage = 'Claim submitted successfully!';
                this.batchIdentifier = response.data.batch_id;
                this.processingDate = response.data.processing_date;

                // Reset form but keep provider name and insurer code
                this.form = {
                    ...this.form,
                    encounter_date: '',
                    specialty: '',
                    priority_level: '',
                    items: [{ name: '', unit_price: 0, quantity: 1 }]
                };
            } catch (error) {
                this.errorMessage = error.response?.data?.message || 'An error occurred while submitting the claim.';
                console.error('Error submitting claim:', error);
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<style scoped>
.claim-form {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

input, select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.suggestions {
    list-style: none;
    padding: 0;
    margin: 5px 0 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.suggestions li {
    padding: 8px;
    cursor: pointer;
}

.suggestions li:hover {
    background-color: #f0f0f0;
}

.items-section {
    margin: 20px 0;
}

.item-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.item-row input {
    flex: 1;
}

.subtotal {
    min-width: 80px;
    text-align: right;
}

.remove-btn {
    background: #ff4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
}

.add-item-btn {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
}

.total-section {
    margin: 20px 0;
    font-weight: bold;
}

.total-section input {
    font-weight: bold;
    font-size: 1.1em;
}

.submit-btn {
    background: #2196F3;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
}

.submit-btn:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

.success-message {
    margin-top: 20px;
    padding: 10px;
    background: #dff0d8;
    border: 1px solid #d6e9c6;
    border-radius: 4px;
    color: #3c763d;
}

.error-message {
    margin-top: 20px;
    padding: 10px;
    background: #f2dede;
    border: 1px solid #ebccd1;
    border-radius: 4px;
    color: #a94442;
}
</style>
