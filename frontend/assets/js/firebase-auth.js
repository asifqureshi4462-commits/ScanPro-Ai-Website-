/**
 * ScanPro AI - Google sign-in (Firebase) for the website.
 *
 * Flow: Google popup -> Firebase ID token -> POST to our PHP API -> PHP verifies the token on the server
 * and starts the normal PHP session -> redirect to the dashboard.
 */
const FIREBASE_VERSION = '12.14.0';
const CDN = `https://www.gstatic.com/firebasejs/${FIREBASE_VERSION}`;
const FRONTEND_BASE = new URL('../../', import.meta.url);
const LOGIN_ENDPOINT = new URL('../../../backend/api/firebase_login.php', import.meta.url).href;

const firebaseConfig = {
    apiKey: 'AIzaSyDQkD3_Yos_UWJluugF110-gonl5AFCa3k',
    authDomain: 'scanpro-ai-117cb.firebaseapp.com',
    projectId: 'scanpro-ai-117cb',
    storageBucket: 'scanpro-ai-117cb.firebasestorage.app',
    messagingSenderId: '731942222919',
    appId: '1:731942222919:web:02ea4cb80758e219f14562'
};

const ERRORS = {
    'auth/popup-closed-by-user': 'The Google window was closed before finishing. Please try again.',
    'auth/popup-blocked': 'Your browser blocked the Google window. Allow pop-ups for this site and try again.',
    'auth/network-request-failed': 'Network problem. Check your connection and try again.',
    'auth/unauthorized-domain': 'This website address is not authorized in Firebase yet. Add it under Authentication > Settings > Authorized domains.',
    'auth/operation-not-allowed': 'Google sign-in is not enabled in Firebase yet. Enable it under Authentication > Sign-in method.',
    'auth/too-many-requests': 'Too many attempts. Please wait a moment and try again.',
    'auth/account-exists-with-different-credential': 'An account with this email already exists with a different sign-in method.'
};

(function init() {
    const block = document.getElementById('googleAuth');
    if (!block) return;
    block.hidden = false;

    const btn = document.getElementById('googleSignInBtn');
    if (!btn) return;
    const label = btn.querySelector('.btn-google-label');
    const errBox = document.getElementById('googleAuthError');

    let sdk = null;

    const showError = (msg) => {
        if (!errBox) return;
        errBox.textContent = msg;
        errBox.hidden = !msg;
    };

    const setLoading = (on, text) => {
        btn.disabled = on;
        btn.setAttribute('aria-busy', on ? 'true' : 'false');
        btn.classList.toggle('is-loading', on);
        if (label) {
            label.textContent = on ? (text || 'Please wait...') : 'Continue with Google';
        }
    };

    const csrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    };

    async function loadSdk() {
        if (sdk) return sdk;
        const [appMod, authMod] = await Promise.all([
            import(`${CDN}/firebase-app.js`),
            import(`${CDN}/firebase-auth.js`)
        ]);
        const app = appMod.initializeApp(firebaseConfig);
        sdk = {
            auth: authMod.getAuth(app),
            GoogleAuthProvider: authMod.GoogleAuthProvider,
            signInWithPopup: authMod.signInWithPopup,
            signOut: authMod.signOut
        };
        return sdk;
    }

    async function firebaseSignOutQuietly() {
        try {
            if (sdk) await sdk.signOut(sdk.auth);
        } catch (e) {
            /* ignore */
        }
    }

    async function exchangeTokenForSession(idToken) {
        const token = csrfToken();
        const response = await fetch(LOGIN_ENDPOINT, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': token
            },
            body: JSON.stringify({ id_token: idToken, csrf_token: token })
        });
        let data = null;
        try {
            data = await response.json();
        } catch (e) {
            /* not JSON */
        }
        if (!data) {
            throw new Error('The server did not answer correctly. Please reload the page and try again.');
        }
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Google sign-in failed. Please try again.');
        }
        return data;
    }

    btn.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();

        showError('');
        setLoading(true, 'Connecting to Google...');
        let succeeded = false;
        try {
            const s = await loadSdk();
            const provider = new s.GoogleAuthProvider();
            provider.setCustomParameters({ prompt: 'select_account' });
            const result = await s.signInWithPopup(s.auth, provider);

            setLoading(true, 'Signing you in...');
            const idToken = await result.user.getIdToken();
            const data = await exchangeTokenForSession(idToken);

            succeeded = true;
            await firebaseSignOutQuietly();
            const targetUrl = new URL(data.redirect || 'dashboard.php', FRONTEND_BASE).href;
            window.location.href = targetUrl;
        } catch (e) {
            const code = e && e.code ? e.code : '';
            if (code === 'auth/cancelled-popup-request') {
                // user clicked twice: ignore silently
            } else if (code) {
                showError(ERRORS[code] || 'Google sign-in failed. Please try again.');
                console.warn('Firebase auth error:', code);
            } else if (e instanceof TypeError || !sdk) {
                showError(sdk ? 'Network problem. Check your connection and try again.' : 'Could not load Google sign-in. Check your connection and try again.');
            } else {
                showError(e.message || 'Google sign-in failed. Please try again.');
            }
            if (!succeeded) await firebaseSignOutQuietly();
        } finally {
            if (!succeeded) setLoading(false);
        }
    });
})();