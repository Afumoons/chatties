import { useEffect, useRef, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import { toUrl } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';

interface User {
    id: number;
    name: string;
    email?: string;
}

interface Message {
    id: number;
    sender_id: number;
    receiver_id: number;
    body: string;
    created_at: string;
    sender: User;
}

export default function Chat() {
    const { auth, users } = usePage().props as any;
    const me: User = auth.user;

    const [selected, setSelected] = useState<User | null>(null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [newBody, setNewBody] = useState('');
    const [typingUsers, setTypingUsers] = useState<Record<number, boolean>>({});
    const [online, setOnline] = useState(false);

    const messagesEnd = useRef<HTMLDivElement>(null);

    useEffect(() => {
        // scroll to bottom when messages change
        messagesEnd.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    useEffect(() => {
        if (selected && typeof window.Echo !== 'undefined') {
            const conversationKey =
                'conversation.' + [me.id, selected.id].sort().join('.');

            const channel = window.Echo.join(`chat.${conversationKey}`);

            channel
                .here((users: any[]) => {
                    // presence: list of currently connected users except me
                    const others = users.filter((u) => u.id !== me.id);
                    setOnline(others.length > 0);
                })
                .joining((user: any) => {
                    if (user.id !== me.id) {
                        setOnline(true);
                    }
                })
                .leaving((user: any) => {
                    if (user.id !== me.id) {
                        setOnline(false);
                    }
                })
                .listen('MessageSent', (e: { message: Message }) => {
                    setMessages((prev) => [...prev, e.message]);
                })
                .listenForWhisper('typing', (e: { user_id: number }) => {
                    setTypingUsers((prev) => ({ ...prev, [e.user_id]: true }));
                    setTimeout(() => {
                        setTypingUsers((prev) => ({
                            ...prev,
                            [e.user_id]: false,
                        }));
                    }, 2000);
                });

            return () => {
                channel.leave();
            };
        }
    }, [me.id, selected]);

    const loadConversation = (user: User) => {
        setSelected(user);
        axios
            .get(toUrl(route('messages.index', user.id)))
            .then((res) => {
                setMessages(res.data.data.reverse()); // pagination newest first
            })
            .catch(console.error);
    };

    const sendMessage = () => {
        if (!selected || newBody.trim() === '') return;
        axios
            .post(toUrl(route('messages.store')), {
                receiver_id: selected.id,
                body: newBody,
            })
            .then((res) => {
                setMessages((prev) => [...prev, res.data]);
                setNewBody('');
            })
            .catch(console.error);
    };

    const handleTyping = () => {
        if (typeof window.Echo !== 'undefined' && selected) {
            const conversationKey =
                'conversation.' + [me.id, selected.id].sort().join('.');
            window.Echo.join(`chat.${conversationKey}`).whisper('typing', {
                user_id: me.id,
            });
        }
    };

    return (
        <AppLayout>
            <Head title="Chat" />
            <div className="flex h-full">
                {/* user list */}
                <aside className="w-64 border-r border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="p-4 font-semibold">Users</div>
                    <ul>
                        {users.map((u: User) => (
                            <li
                                key={u.id}
                                className={`cursor-pointer px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 ${
                                    selected?.id === u.id
                                        ? 'bg-gray-200 dark:bg-gray-700'
                                        : ''
                                }`}
                                onClick={() => loadConversation(u)}
                            >
                                {u.name}
                                {typingUsers[u.id] && (
                                    <span className="ml-2 text-xs italic">
                                        typing...
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                </aside>

                {/* conversation area */}
                <main className="flex flex-1 flex-col">
                    {selected && (
                        <div className="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                            <span className="font-semibold">
                                {selected.name}
                            </span>
                            {online && (
                                <span className="ml-2 inline-block h-2 w-2 rounded-full bg-green-500" />
                            )}
                        </div>
                    )}
                    <div className="flex-1 overflow-y-auto p-4">
                        {selected ? (
                            messages.map((msg) => (
                                <div
                                    key={msg.id}
                                    className={`mb-2 flex ${msg.sender_id === me.id ? 'justify-end' : ''}`}
                                >
                                    <div
                                        className={`max-w-[60%] rounded-lg p-2 whitespace-pre-wrap ${
                                            msg.sender_id === me.id
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        }`}
                                    >
                                        {msg.body}
                                        <div className="mt-1 text-xs text-gray-500">
                                            {new Date(
                                                msg.created_at,
                                            ).toLocaleTimeString()}
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="mt-20 text-center text-gray-500">
                                Select a user to start chatting
                            </div>
                        )}
                        <div ref={messagesEnd}></div>
                    </div>
                    {selected && (
                        <div className="flex border-t border-sidebar-border/70 p-4 dark:border-sidebar-border">
                            <input
                                type="text"
                                className="flex-1 rounded border px-3 py-2"
                                value={newBody}
                                onChange={(e) => setNewBody(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        sendMessage();
                                    } else {
                                        handleTyping();
                                    }
                                }}
                                placeholder="Type a message..."
                            />
                            <button
                                className="ml-2 rounded bg-blue-600 px-4 py-2 text-white"
                                onClick={sendMessage}
                            >
                                Send
                            </button>
                        </div>
                    )}
                </main>
            </div>
        </AppLayout>
    );
}
