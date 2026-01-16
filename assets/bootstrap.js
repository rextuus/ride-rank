// assets/bootstrap.js
import { startStimulusApp } from '@symfony/stimulus-bridge';

// Live Components importieren
import LiveController from '@symfony/ux-live-component';

const application = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

application.register('live', LiveController);
