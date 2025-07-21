require('dotenv').config();
const axios = require('axios');
const fs = require('fs');
const FormData = require('form-data');
const path = require('path');

const [, , filePath] = process.argv;
const PINATA_JWT = process.env.PINATA_JWT;

async function main() {
    try {
        const fileStream = fs.createReadStream(path.resolve(filePath));

        const form = new FormData();
        form.append('file', fileStream);

        const response = await axios.post(
            'https://api.pinata.cloud/pinning/pinFileToIPFS',
            form,
            {
                maxBodyLength: 'Infinity',
                headers: {
                    'Authorization': `Bearer ${PINATA_JWT}`,
                    ...form.getHeaders()
                }
            }
        );

        const cid = response.data.IpfsHash;
        console.log('ipfs://' + cid);
    } catch (err) {
        console.error('Upload failed:', err.message);
        process.exit(1);
    }
}

main();
