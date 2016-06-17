/**
 * ServerInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ServerInfo  implements java.io.Serializable {
    private java.lang.String name;

    private java.lang.String URL;

    private java.lang.String developer;

    private java.lang.String implementation;

    private java.lang.String technology;

    private java.lang.String version;

    private com.woodwing.enterprise.interfaces.services.wfl.Feature[] featureSet;

    private java.lang.String cryptKey;

    private java.lang.String enterpriseSystemId;

    public ServerInfo() {
    }

    public ServerInfo(
           java.lang.String name,
           java.lang.String URL,
           java.lang.String developer,
           java.lang.String implementation,
           java.lang.String technology,
           java.lang.String version,
           com.woodwing.enterprise.interfaces.services.wfl.Feature[] featureSet,
           java.lang.String cryptKey,
           java.lang.String enterpriseSystemId) {
           this.name = name;
           this.URL = URL;
           this.developer = developer;
           this.implementation = implementation;
           this.technology = technology;
           this.version = version;
           this.featureSet = featureSet;
           this.cryptKey = cryptKey;
           this.enterpriseSystemId = enterpriseSystemId;
    }


    /**
     * Gets the name value for this ServerInfo.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this ServerInfo.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the URL value for this ServerInfo.
     * 
     * @return URL
     */
    public java.lang.String getURL() {
        return URL;
    }


    /**
     * Sets the URL value for this ServerInfo.
     * 
     * @param URL
     */
    public void setURL(java.lang.String URL) {
        this.URL = URL;
    }


    /**
     * Gets the developer value for this ServerInfo.
     * 
     * @return developer
     */
    public java.lang.String getDeveloper() {
        return developer;
    }


    /**
     * Sets the developer value for this ServerInfo.
     * 
     * @param developer
     */
    public void setDeveloper(java.lang.String developer) {
        this.developer = developer;
    }


    /**
     * Gets the implementation value for this ServerInfo.
     * 
     * @return implementation
     */
    public java.lang.String getImplementation() {
        return implementation;
    }


    /**
     * Sets the implementation value for this ServerInfo.
     * 
     * @param implementation
     */
    public void setImplementation(java.lang.String implementation) {
        this.implementation = implementation;
    }


    /**
     * Gets the technology value for this ServerInfo.
     * 
     * @return technology
     */
    public java.lang.String getTechnology() {
        return technology;
    }


    /**
     * Sets the technology value for this ServerInfo.
     * 
     * @param technology
     */
    public void setTechnology(java.lang.String technology) {
        this.technology = technology;
    }


    /**
     * Gets the version value for this ServerInfo.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this ServerInfo.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }


    /**
     * Gets the featureSet value for this ServerInfo.
     * 
     * @return featureSet
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Feature[] getFeatureSet() {
        return featureSet;
    }


    /**
     * Sets the featureSet value for this ServerInfo.
     * 
     * @param featureSet
     */
    public void setFeatureSet(com.woodwing.enterprise.interfaces.services.wfl.Feature[] featureSet) {
        this.featureSet = featureSet;
    }


    /**
     * Gets the cryptKey value for this ServerInfo.
     * 
     * @return cryptKey
     */
    public java.lang.String getCryptKey() {
        return cryptKey;
    }


    /**
     * Sets the cryptKey value for this ServerInfo.
     * 
     * @param cryptKey
     */
    public void setCryptKey(java.lang.String cryptKey) {
        this.cryptKey = cryptKey;
    }


    /**
     * Gets the enterpriseSystemId value for this ServerInfo.
     * 
     * @return enterpriseSystemId
     */
    public java.lang.String getEnterpriseSystemId() {
        return enterpriseSystemId;
    }


    /**
     * Sets the enterpriseSystemId value for this ServerInfo.
     * 
     * @param enterpriseSystemId
     */
    public void setEnterpriseSystemId(java.lang.String enterpriseSystemId) {
        this.enterpriseSystemId = enterpriseSystemId;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ServerInfo)) return false;
        ServerInfo other = (ServerInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.URL==null && other.getURL()==null) || 
             (this.URL!=null &&
              this.URL.equals(other.getURL()))) &&
            ((this.developer==null && other.getDeveloper()==null) || 
             (this.developer!=null &&
              this.developer.equals(other.getDeveloper()))) &&
            ((this.implementation==null && other.getImplementation()==null) || 
             (this.implementation!=null &&
              this.implementation.equals(other.getImplementation()))) &&
            ((this.technology==null && other.getTechnology()==null) || 
             (this.technology!=null &&
              this.technology.equals(other.getTechnology()))) &&
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion()))) &&
            ((this.featureSet==null && other.getFeatureSet()==null) || 
             (this.featureSet!=null &&
              java.util.Arrays.equals(this.featureSet, other.getFeatureSet()))) &&
            ((this.cryptKey==null && other.getCryptKey()==null) || 
             (this.cryptKey!=null &&
              this.cryptKey.equals(other.getCryptKey()))) &&
            ((this.enterpriseSystemId==null && other.getEnterpriseSystemId()==null) || 
             (this.enterpriseSystemId!=null &&
              this.enterpriseSystemId.equals(other.getEnterpriseSystemId())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getURL() != null) {
            _hashCode += getURL().hashCode();
        }
        if (getDeveloper() != null) {
            _hashCode += getDeveloper().hashCode();
        }
        if (getImplementation() != null) {
            _hashCode += getImplementation().hashCode();
        }
        if (getTechnology() != null) {
            _hashCode += getTechnology().hashCode();
        }
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
        }
        if (getFeatureSet() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFeatureSet());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFeatureSet(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getCryptKey() != null) {
            _hashCode += getCryptKey().hashCode();
        }
        if (getEnterpriseSystemId() != null) {
            _hashCode += getEnterpriseSystemId().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(ServerInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ServerInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("URL");
        elemField.setXmlName(new javax.xml.namespace.QName("", "URL"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("developer");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Developer"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("implementation");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Implementation"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("technology");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Technology"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("featureSet");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FeatureSet"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Feature"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Feature"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("cryptKey");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CryptKey"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("enterpriseSystemId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EnterpriseSystemId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
