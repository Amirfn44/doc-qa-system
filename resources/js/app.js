import './bootstrap';
import './workspace';
import {
    createIcons, SquarePen, Plus, FileUp, FileText, X, ArrowUp, Trash2,
    NotebookTabs, MessagesSquare, ScanText, BookOpen, Check, Copy, Search,
    LoaderCircle, CircleHelp, RotateCw,
} from 'lucide';

const icons = {
    SquarePen, Plus, FileUp, FileText, X, ArrowUp, Trash2,
    NotebookTabs, MessagesSquare, ScanText, BookOpen, Check, Copy, Search,
    LoaderCircle, CircleHelp, RotateCw,
};
const iconPlaceholder = '[data-lucide]:not(svg)';
const iconObserver = new MutationObserver((mutations) => {
    const hasNewIcons = mutations.some(({ addedNodes }) =>
        Array.from(addedNodes).some((node) => node instanceof Element && (
            node.matches(iconPlaceholder) || node.querySelector(iconPlaceholder)
        ))
    );

    if (hasNewIcons) {
        refreshIcons();
    }
});

function refreshIcons() {
    // Lucide keeps data-lucide on its SVGs. Do not observe its replacements:
    // otherwise rendering an icon schedules another render indefinitely.
    iconObserver.disconnect();
    try {
        createIcons({ icons });
    } finally {
        iconObserver.observe(document.body, { childList: true, subtree: true });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshIcons, { once: true });
} else {
    refreshIcons();
}
