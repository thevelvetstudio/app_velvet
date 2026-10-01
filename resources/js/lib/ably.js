import * as Ably from 'ably';

let realtime;

export function subscribeToRealtime(onUpdate, eventName = null) {
    if (!import.meta.env.VITE_ABLY_ENABLED) return () => {};
    if (!realtime) {
        realtime = new Ably.Realtime({ authUrl: '/realtime/token', authMethod: 'GET' });
        realtime.connection.on((stateChange) => {
            if (['failed', 'suspended'].includes(stateChange.current)) {
                console.error('[Ably] conexión realtime no disponible:', stateChange.reason || stateChange.current);
            }
        });
    }
    const channelNames = (import.meta.env.VITE_ABLY_CHANNELS || import.meta.env.VITE_ABLY_CHANNEL || 'admin-leads').split(',').map((name) => name.trim()).filter(Boolean);
    const roomsChannel = import.meta.env.VITE_ABLY_ROOMS_CHANNEL || 'admin-rooms';
    if (!channelNames.includes(roomsChannel)) channelNames.push(roomsChannel);
    const channels = channelNames.map((name) => realtime.channels.get(name));
    const handler = (message) => {
        let data = message.data;
        if (typeof data === 'string') { try { data = JSON.parse(data); } catch { return; } }
        onUpdate(data, message.name);
    };
    channels.forEach((channel) => eventName ? channel.subscribe(eventName, handler) : channel.subscribe(handler));
    return () => channels.forEach((channel) => eventName ? channel.unsubscribe(eventName, handler) : channel.unsubscribe(handler));
}

export function subscribeToLeadUpdates(onUpdate) {
    return subscribeToRealtime(onUpdate, 'lead.status_changed');
}

