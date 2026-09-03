const axios = require('axios');

const BASE_URL = 'http://localhost:3100/api/v1'; // Твой внешний порт докера
const SKU = 'KEY-GTA5';
const PAYMENT_ID = 'pay_tx_js_' + Math.random().toString(36).substring(7);

async function runTest() {
    console.log('🚀 Старт E2E теста гонок на Node.js...');

    try {
        // 1. Создаем заказ через API
        const orderRes = await axios.post(`${BASE_URL}/orders`, { sku: SKU }, {
            headers: {
                'Idempotency-Key': 'order_key_js_' + PAYMENT_ID,
                'Accept': 'application/json'
            }
        });

        const orderId = orderRes.data.data.order_id;
        console.log(`✅ Заказ успешно создан через API. ID: ${orderId}`);

        // 2. Готовим 10 параллельных запросов оплаты (вебхуков)
        const payload = {
            event_id: 'evt_js_' + orderId,
            payment_id: PAYMENT_ID,
            order_id: orderId,
            status: 'paid',
            amount: 499,
            currency: 'RUB',
            created_at: new Date().toISOString()
        };

        const headers = {
            'Idempotency-Key': PAYMENT_ID,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        console.log(`🔥 Отправляем 10 параллельных вебхуков оплаты одновременно...`);

        // Запускаем их строго параллельно через Promise.all
        const requests = Array.from({ length: 10 }).map((_, i) =>
            axios.post(`${BASE_URL}/payments/webhook`, payload, { headers })
                .then(res => `Запрос #${i+1} | Статус: ${res.status} | Ответ: ${JSON.stringify(res.data).substring(0, 40)}`)
                .catch(err => `Запрос #${i+1} | Ошибка: ${err.response ? err.response.status : err.message} | Ответ: ${err.response ? JSON.stringify(err.response.data) : ''}`)
        );

        const results = await Promise.all(requests);
        results.forEach(res => console.log(res));

        console.log('\n🏁 Проверка завершена. Проверь статус заказа в базе данных PostgreSQL!');

    } catch (error) {
        console.error('❌ Критическая ошибка теста:', error.response ? error.response.data : error.message);
    }
}

runTest();
