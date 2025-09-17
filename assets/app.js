import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.scss';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

import { createIcons, Star, Folder, Users, ArrowRightCircle, ArrowLeftCircle, SkipForward } from 'lucide';

document.addEventListener("DOMContentLoaded", () => {
    createIcons({
        icons: {
            Star,
            Folder,
            Users,
            ArrowRightCircle,
            ArrowLeftCircle,
            SkipForward
        }
    });
});

document.addEventListener("live:render", () => {
    createIcons({
        icons: {
            Star,
            Folder,
            Users,
            ArrowRightCircle,
            ArrowLeftCircle,
            SkipForward
        }
    });
});
