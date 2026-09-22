export const notificationDeliveryStatuses = {
    pending: {
        label: "Pendente",
        color: "warning",
    },
    sent: {
        label: "Enviado",
        color: "success",
    },
    failed: {
        label: "Falhou",
        color: "error",
    },
};

export function getNotificationDeliveryStatusLabel(status) {
    return notificationDeliveryStatuses[status]?.label || status;
}

export function getNotificationDeliveryStatusColor(status) {
    return notificationDeliveryStatuses[status]?.color || "default";
}
