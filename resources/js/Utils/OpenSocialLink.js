const PLATFORMS = {
    facebook: {
        web: "https://www.facebook.com/profile.php?id=61593682967342",
        app: "fb://profile/61593682967342",
        androidIntent: "intent://profile/61593682967342#Intent;scheme=fb;package=com.facebook.katana;S.browser_fallback_url=https%3A%2F%2Fwww.facebook.com%2Fprofile.php%3Fid%3D61593682967342;end",
    },
    instagram: {
        web: "https://www.instagram.com/yuri97social/",
        app: "instagram://user?username=yuri97social",
        androidIntent: "intent://instagram.com/_u/yuri97social/#Intent;package=com.instagram.android;scheme=https;S.browser_fallback_url=https%3A%2F%2Fwww.instagram.com%2Fyuri97social%2F;end",
    },
    x: {
        web: "https://x.com/yuri97social",
        app: "twitter://user?screen_name=yuri97social",
        androidIntent: "intent://user?screen_name=yuri97social#Intent;scheme=twitter;package=com.twitter.android;S.browser_fallback_url=https%3A%2F%2Fx.com%2Fyuri97social;end",
    },
    youtube: {
        web: "https://www.youtube.com/@yuri97social",
        app: "vnd.youtube://www.youtube.com/@yuri97social",
        androidIntent: "intent://www.youtube.com/@yuri97social#Intent;package=com.google.android.youtube;scheme=https;S.browser_fallback_url=https%3A%2F%2Fwww.youtube.com%2F%40yuri97social;end",
    },
    linkedin: {
        web: "https://www.linkedin.com/in/yuri97dev/",
        app: "linkedin://in/yuri97dev",
        androidIntent: "intent://www.linkedin.com/in/yuri97dev/#Intent;package=com.linkedin.android;scheme=https;S.browser_fallback_url=https%3A%2F%2Fwww.linkedin.com%2Fin%2Fyuri97dev%2F;end",
    },
};

export const SOCIAL_ITEMS = [
    { platform: "facebook", label: "Facebook", icon: "mdi-facebook" },
    { platform: "instagram", label: "Instagram", icon: "mdi-instagram" },
    { platform: "x", label: "X", icon: "mdi-twitter" },
    { platform: "youtube", label: "YouTube", icon: "mdi-youtube" },
    { platform: "linkedin", label: "LinkedIn", icon: "mdi-linkedin" },
];

function isMobile() {
    return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
}

function isAndroid() {
    return /Android/i.test(navigator.userAgent);
}

export function getSocialWebUrl(platform) {
    return PLATFORMS[platform]?.web ?? null;
}

export function openSocialLink(platform) {
    const config = PLATFORMS[platform];

    if( !config ) return;

    if( !isMobile() ) {
        window.open(config.web, "_blank", "noopener,noreferrer");
        return;
    }

    if( isAndroid() ) {
        window.location.href = config.androidIntent;
        return;
    }

    let appOpened = false;

    const handleVisibilityChange = () => {
        if( document.hidden ) {
            appOpened = true;
        }
    };

    document.addEventListener("visibilitychange", handleVisibilityChange);

    window.location.href = config.app;

    setTimeout(() => {
        document.removeEventListener("visibilitychange", handleVisibilityChange);

        if( !appOpened ) {
            window.open(config.web, "_blank", "noopener,noreferrer");
        }
    }, 1200);
}

export function handleSocialClick(event, platform) {
    if( !isMobile() ) return;

    event.preventDefault();

    openSocialLink(platform);
}
