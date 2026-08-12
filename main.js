const { app, BrowserWindow, Menu } = require('electron');
const path = require('path');

let mainWindow;

// Create window when Electron is ready
app.on('ready', () => {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1000,
        minHeight: 700,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true
        },
        icon: path.join(__dirname, 'icon.png')
    });

    // Load your PHP app from localhost
    mainWindow.loadURL('http://localhost/sabong-new/index.php');

    // Remove default menu (clean look like LoL)
    Menu.setApplicationMenu(null);

    // Open DevTools for debugging (comment out for production)
    // mainWindow.webContents.openDevTools();

    // Handle window closed
    mainWindow.on('closed', () => {
        app.quit();
    });

    // Handle refresh
    mainWindow.webContents.on('before-input-event', (event, input) => {
        if (input.control && input.key.toLowerCase() === 'r') {
            event.preventDefault();
        }
        if (input.key === 'F5') {
            event.preventDefault();
        }
    });
});

// Quit when all windows are closed
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

// Handle any uncaught exceptions
process.on('uncaughtException', (error) => {
    console.error('Uncaught Exception:', error);
});