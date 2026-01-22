export type ViaCepResponse = {
    cep: string;
    logradouro: string;
    complemento: string;
    bairro: string;
    localidade: string;
    uf: string;
    erro?: boolean;
};

export type AddressResolved = {
    street: string;
    neighborhood: string;
    city: string;
    state: string;
    zip_code: string;
    latitude: number;
    longitude: number;
};

function onlyDigits(value: string) {
    return value.replace(/\D+/g, "");
}

export async function fetchViaCep(zipCode: string): Promise<ViaCepResponse> {
    const zip = onlyDigits(zipCode);
    if (zip.length !== 8) throw new Error("CEP inválido.");

    const res = await fetch(`https://viacep.com.br/ws/${zip}/json/`, {
        headers: { Accept: "application/json" },
    });

    if (!res.ok) throw new Error("Falha ao consultar ViaCEP.");

    const data = (await res.json()) as ViaCepResponse;

    if ((data as any)?.erro) throw new Error("CEP não encontrado.");

    return data;
}

export async function geocodeNominatim(query: string) {
    const url = new URL("https://nominatim.openstreetmap.org/search");
    url.searchParams.set("format", "json");
    url.searchParams.set("limit", "1");
    url.searchParams.set("q", query);

    const res = await fetch(url.toString(), {
        headers: {
            Accept: "application/json",
        },
    });

    // Se bater rate limit, mostre mensagem clara
    if (res.status === 429) {
        throw new Error("Muitas buscas em pouco tempo. Aguarde alguns segundos e tente novamente.");
    }

    if (!res.ok) throw new Error("Falha ao geocodificar endereço.");

    const data = (await res.json()) as Array<{ lat: string; lon: string }>;
    if (!Array.isArray(data) || data.length === 0) throw new Error("EMPTY");

    const latitude = Number(data[0].lat);
    const longitude = Number(data[0].lon);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        throw new Error("Coordenadas inválidas.");
    }

    return { latitude, longitude };
}

export async function geocodeWithFallback(queries: string[]) {
    let lastErr: any = null;

    for (const q of queries) {
        try {
            return await geocodeNominatim(q);
        } catch (e: any) {
            lastErr = e;
            // se for rate limit, não adianta continuar
            if (e?.message?.includes("Muitas buscas")) throw e;
        }
    }

    // Se todas deram vazio, mensagem “produto”
    throw new Error(
        "Não foi possível obter coordenadas para este endereço. Tente ajustar o número ou digite a latitude/longitude manualmente."
    );
}

export async function resolveAddressFromCepAndNumber(zipCode: string, number: string): Promise<AddressResolved> {
    const via = await fetchViaCep(zipCode);

    const street = via.logradouro?.trim();
    const neighborhood = via.bairro?.trim();
    const city = via.localidade?.trim();
    const state = via.uf?.trim();

    if (!street || !city || !state) {
        throw new Error("ViaCEP não retornou endereço suficiente para geocodificação.");
    }

    // Monta query forte (número + rua + cidade + UF + CEP)
    const zipDigits = onlyDigits(zipCode);
    const q = `${number} ${street}, ${neighborhood}, ${city} - ${state}, ${zipDigits}, Brasil`;

    const { latitude, longitude } = await geocodeNominatim(q);

    return {
        street,
        neighborhood: neighborhood || "",
        city,
        state,
        zip_code: zipCode,
        latitude,
        longitude,
    };
}
