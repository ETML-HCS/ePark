import './bootstrap';
import 'leaflet/dist/leaflet.css';

import Alpine from 'alpinejs';
import expressBooking from './express-booking';
import { parkingMap, sitePickerMap } from './parking-map';

window.Alpine = Alpine;
Alpine.data('expressBooking', expressBooking);
Alpine.data('parkingMap', parkingMap);
Alpine.data('sitePickerMap', sitePickerMap);

Alpine.start();

if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
