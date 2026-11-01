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

import { daysBetween, inputDate, kteoStatus, timeOfDay } from '../../resources/js/workshop/Components/dates.js';
test('KTEO and next-service dates preserve the Athens calendar day', () => {
    assert.equal(inputDate('2026-09-25T21:00:00.000000Z'), '2026-09-26');
    assert.equal(inputDate('2026-09-26'), '2026-09-26');
});

test('days in the shop count Athens calendar days, not elapsed hours', () => {
    const now = new Date('2026-10-22T10:00:00Z');
    assert.equal(daysBetween('2026-10-22T08:00:00Z', now), 0);
    assert.equal(daysBetween('2026-10-20T21:30:00Z', now), 1);
    assert.equal(daysBetween('2026-10-17T09:00:00Z', now), 5);
    assert.equal(daysBetween(now, '2026-10-27'), 5);
    assert.equal(daysBetween(null, now), null);
});

test('KTEO status handles missing, expired, imminent and distant dates', () => {
    const now = new Date('2026-10-22T10:00:00Z');
    assert.equal(kteoStatus({ kteo_expires_at: null }, { now }), null);
    assert.deepEqual(kteoStatus({ kteo_expires_at: '2026-10-17' }, { now }), { tone: 'danger', label: 'Έληξε πριν 5 ημ.' });
    assert.deepEqual(kteoStatus({ kteo_expires_at: '2026-10-22' }, { now }), { tone: 'danger', label: 'Λήγει σήμερα' });
    assert.deepEqual(kteoStatus({ kteo_expires_at: '2026-10-26' }, { now }), { tone: 'warning', label: 'Σε 4 ημέρες' });
    assert.equal(kteoStatus({ kteo_expires_at: '2027-01-25' }, { now, quiet: true }), null);
    assert.equal(kteoStatus({ kteo_expires_at: '2027-01-25' }, { now }).tone, 'calm');
});

test('appointment time is shown on the 24-hour Athens clock', () => {
    assert.equal(timeOfDay('2026-10-22T06:30:00Z'), '09:30');
    assert.equal(timeOfDay('2026-10-22T21:05:00Z'), '00:05');
    assert.equal(timeOfDay(null), '—');
});
