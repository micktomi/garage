import test from 'node:test';
import assert from 'node:assert/strict';
import { workOrderPayload } from '../../resources/js/workshop/Pages/WorkOrders/payload.js';
import { compatibleModels, modelAfterMakeChange } from '../../resources/js/workshop/Components/makeModel.js';
const catalogue = { Toyota: ['Yaris', 'Corolla'], Volkswagen: ['Golf', 'Polo'] };
test('make suggestions use the existing catalogue and changing make clears stale model', () => {
    assert.deepEqual(compatibleModels(catalogue, ' toyota '), ['Yaris', 'Corolla']);
    assert.equal(modelAfterMakeChange(catalogue, 'Toyota', 'Yaris', 'Volkswagen'), '');
    assert.equal(modelAfterMakeChange(catalogue, 'Toyota', 'Custom OCR model', ' toyota '), 'Custom OCR model');
    assert.deepEqual(compatibleModels(catalogue, 'Custom manufacturer'), []);
});
test('real form payload is plural, preserves rows and excludes unprivileged purchase costs', () => {
    const data = { parts: [{ source: 'from_stock', part_id: 4, quantity: 2, unit_price: 12, unit_cost: 8 }, { source: 'customer_supplied', description: 'Υλικό', quantity: 1, unit_price: 0 }] };
    const payload = workOrderPayload(data, false);
    assert.equal(payload.parts.length, 2);
    assert.equal(payload.parts[0].part_id, 4);
    assert.equal('unit_cost' in payload.parts[0], false);
    assert.equal('part' in payload, false);
});

import { inputDate } from '../../resources/js/workshop/Components/dates.js';
test('KTEO and next-service dates preserve the Athens calendar day', () => {
    assert.equal(inputDate('2026-09-25T21:00:00.000000Z'), '2026-09-26');
    assert.equal(inputDate('2026-09-26'), '2026-09-26');
});
