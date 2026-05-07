<template>
    <section class="bg-[#ececee] py-10 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h1 class="home-display text-2xl font-bold uppercase tracking-tight text-gray-900 sm:text-3xl">Корзина</h1>
                <p class="mt-2 text-xs text-gray-600">Оформите бронь: проверьте позиции и отправьте заявку.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                        <p class="site-header-font text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-600">
                            Всего: <span class="text-gray-900">{{ cartItems.length }}</span>
                        </p>
                        <button
                            type="button"
                            class="site-header-font border border-gray-300 px-3 py-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-700 transition hover:border-[#05299E] hover:text-[#05299E] disabled:opacity-50"
                            :disabled="!cartItems.length || isSubmitting"
                            @click="clearCart"
                        >
                            Очистить
                        </button>
                    </div>

                    <div v-if="!cartItems.length" class="rounded-lg border border-dashed border-gray-300 bg-[#fafafa] p-8 text-center text-sm text-gray-500">
                        Корзина пуста.
                        <a href="/map/app" class="ml-2 font-semibold text-[#05299E] hover:text-[#041d6b]">Карта</a>
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="(item, index) in cartItems"
                            :key="`${item.advertisementId}-${item.side}-${item.startDate}-${item.endDate}`"
                            class="rounded-lg border border-gray-200 bg-[#fafafa] p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ item.address }}</p>
                                    <p class="mt-1 truncate text-xs text-gray-600">{{ item.side }} • {{ item.startDate }}—{{ item.endDate }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <p class="text-xs font-semibold text-[#05299E]">{{ formatPrice(item.price) }}</p>
                                    <button
                                        type="button"
                                        class="site-header-font border border-gray-300 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-700 transition hover:border-[#e85d4c] hover:text-[#e85d4c] disabled:opacity-50"
                                        :disabled="isSubmitting"
                                        @click="removeCartItem(index)"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <aside class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="home-display text-sm font-bold uppercase tracking-[0.16em] text-[#05299E]">Оформление заказа</h2>

                    <div class="mt-4 border-b border-gray-100 pb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Итого</span>
                            <span class="text-base font-bold text-gray-900">{{ formatPrice(cartTotal) }}</span>
                        </div>
                    </div>

                    <form class="mt-4 space-y-3" @submit.prevent="submitOrder">
                        <label class="block text-sm">
                            <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Имя</span>
                            <input v-model.trim="orderForm.name" :readonly="isAuthenticated" required class="w-full border border-gray-300 px-3 py-2.5" />
                        </label>
                        <label class="block text-sm">
                            <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Телефон</span>
                            <input v-model.trim="orderForm.phone" :readonly="isAuthenticated" required class="w-full border border-gray-300 px-3 py-2.5" />
                        </label>
                        <label class="block text-sm">
                            <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Комментарий</span>
                            <textarea v-model.trim="orderForm.comment" rows="3" class="w-full border border-gray-300 px-3 py-2.5"></textarea>
                        </label>
                        <input v-model="orderForm.website" type="text" autocomplete="off" class="hidden" tabindex="-1" />

                        <p v-if="statusMessage" class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ statusMessage }}</p>

                        <button
                            type="submit"
                            :disabled="!cartItems.length || isSubmitting"
                            class="site-header-font w-full bg-[#05299E] px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-white transition hover:bg-[#041d6b] disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Отправка…' : 'Отправить заказ' }}
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const props = defineProps({
    cartUrl: { type: String, required: true },
    ordersUrl: { type: String, required: true },
    authUser: { type: Object, required: true },
})

const cartItems = ref([])
const isSubmitting = ref(false)
const statusMessage = ref('')
const orderForm = reactive({ name: '', phone: '', comment: '', website: '', startedAt: Date.now() })

const normalizedOrdersUrl = computed(() => String(props.ordersUrl || '').replace(/\/+$/, ''))
const isAuthenticated = computed(() => Boolean(props.authUser?.isAuthenticated))
const cartTotal = computed(() => cartItems.value.reduce((sum, item) => sum + (Number(item.price) || 0), 0))

function formatPrice(price) {
    if (!price) return 'По запросу'
    return `${new Intl.NumberFormat('ru-RU').format(price)} ₽`
}

function applyCartPayload(data) {
    cartItems.value = Array.isArray(data?.items) ? data.items : []
}

async function loadCart() {
    try {
        const response = await fetch(props.cartUrl)
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Ошибка загрузки корзины.')
        }
        applyCartPayload(data)
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Не удалось загрузить корзину.'
    }
}

async function removeCartItem(index) {
    statusMessage.value = ''
    try {
        const response = await fetch(`${props.cartUrl}/items/${index}`, { method: 'DELETE' })
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Не удалось удалить позицию.')
        }
        applyCartPayload(data)
        statusMessage.value = data?.message || 'Позиция удалена.'
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Не удалось удалить позицию.'
    }
}

async function clearCart() {
    statusMessage.value = ''
    try {
        const response = await fetch(props.cartUrl, { method: 'DELETE' })
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Не удалось очистить корзину.')
        }
        applyCartPayload(data)
        statusMessage.value = data?.message || 'Корзина очищена.'
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Не удалось очистить корзину.'
    }
}

async function submitOrder() {
    if (!cartItems.value.length) {
        statusMessage.value = 'Добавьте позиции в корзину.'
        return
    }

    isSubmitting.value = true
    statusMessage.value = ''

    if (isAuthenticated.value) {
        orderForm.name = props.authUser?.name || orderForm.name
        orderForm.phone = props.authUser?.phone || orderForm.phone
    }

    try {
        const response = await fetch(normalizedOrdersUrl.value || '/api/orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                website: orderForm.website,
                formStartedAt: orderForm.startedAt,
                contactName: orderForm.name,
                contactPhone: orderForm.phone,
                comment: orderForm.comment || null,
                items: cartItems.value,
                userId: props.authUser?.id ?? null,
                userEmail: props.authUser?.email || null,
                isAuthenticated: isAuthenticated.value,
            }),
        })
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Ошибка при отправке заказа.')
        }
        statusMessage.value = data?.message || 'Заказ отправлен.'
        orderForm.startedAt = Date.now()
        await loadCart()
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Ошибка при отправке заказа.'
    } finally {
        isSubmitting.value = false
    }
}

onMounted(async () => {
    const initialSpinner = document.getElementById('initial-cart-spinner')
    if (initialSpinner) {
        initialSpinner.remove()
    }

    if (isAuthenticated.value) {
        orderForm.name = props.authUser?.name || ''
        orderForm.phone = props.authUser?.phone || ''
    }

    await loadCart()
})
</script>

