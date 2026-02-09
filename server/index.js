const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const { RtcTokenBuilder, RtcRole } = require('agora-access-token');

dotenv.config();
console.log("ENV CHECK", {
    AGORA_APP_ID: process.env.AGORA_APP_ID,
    AGORA_APP_CERTIFICATE: process.env.AGORA_APP_CERTIFICATE
});
const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

const APP_ID = process.env.AGORA_APP_ID;
const APP_CERTIFICATE = process.env.AGORA_APP_CERTIFICATE;

const nocache = (_, resp, next) => {
    resp.header('Cache-Control', 'private, no-cache, no-store, must-revalidate');
    resp.header('Expires', '-1');
    resp.header('Pragma', 'no-cache');
    next();
};

const generateAccessToken = (req, resp) => {
    // set response header
    resp.header('Access-Control-Allow-Origin', '*');

    const channelName = req.query.channelName;
    if (!channelName) {
        return resp.status(500).json({ 'error': 'channel is required' });
    }

    // get uid
    let uid = req.query.uid;
    if (!uid || uid === '') {
        uid = 0;
    }

    // get role
    let role = RtcRole.SUBSCRIBER;
    if (req.query.role === 'publisher') {
        role = RtcRole.PUBLISHER;
    }

    // get the expire time
    let expireTime = req.query.expireTime;
    if (!expireTime || expireTime === '') {
        expireTime = 3600;
    } else {
        expireTime = parseInt(expireTime, 10);
    }

    // calculate privilege expire time
    const currentTime = Math.floor(Date.now() / 1000);
    const privilegeExpireTime = currentTime + expireTime;

    console.log("Generating Token:");
    console.log("App Id:", APP_ID);
    console.log("App Certificate:", APP_CERTIFICATE);
    console.log("Channel Name:", channelName);
    console.log("Uid:", uid);
    console.log("Role:", role);
    console.log("Privilege Expire Time:", privilegeExpireTime);

    const token = RtcTokenBuilder.buildTokenWithUid(
        APP_ID,
        APP_CERTIFICATE,
        channelName,
        Number(uid),
        role,
        privilegeExpireTime
    );


    return resp.json({ 'token': token });
};

app.get('/rtc-token', nocache, generateAccessToken);

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Listening on port: ${PORT}`);
});
