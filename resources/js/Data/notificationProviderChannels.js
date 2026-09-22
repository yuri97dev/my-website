export const notificationProviderChannels = {
    discord: {
        title: "Discord",
        configFields: [
            {
                key: "webhook_url",
                label: "URL do Webhook",
                type: "secret",
                keepOnEdit: true,
            },
        ],
    },
};

export const notificationProviderChannelOptions = Object.entries(notificationProviderChannels).map(([value, channel]) => ({
    value,
    title: channel.title,
}));

export function getNotificationProviderChannel(channel) {
    return notificationProviderChannels[channel];
}

export function getDefaultNotificationProviderChannel() {
    return Object.keys(notificationProviderChannels)[0] ?? null;
}

export function getNotificationProviderChannelLabel(channel) {
    return getNotificationProviderChannel(channel)?.title || channel;
}

export function buildEmptyProviderConfig(channel) {
    const definition = getNotificationProviderChannel(channel);

    if( !definition ) {
        return {};
    }

    return Object.fromEntries(
        definition.configFields.map(field => [field.key, null]),
    );
}

export function buildProviderConfigPayload(channel, config = {}) {
    const definition = getNotificationProviderChannel(channel);

    if( !definition ) {
        return {};
    }

    const payload = {};

    for( const field of definition.configFields ) {
        const value = config[field.key];

        if( value ) {
            payload[field.key] = value;
        }
    }

    return payload;
}
