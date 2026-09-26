import './bootstrap';
import { createIcons, Pencil, Plus, Paperclip, FileText, X, Send, Trash2 } from 'lucide';

const refreshIcons = () => createIcons({ icons: { Pencil, Plus, Paperclip, FileText, X, Send, Trash2 } });
document.addEventListener('DOMContentLoaded', refreshIcons);
new MutationObserver(refreshIcons).observe(document.body, { childList: true, subtree: true });
