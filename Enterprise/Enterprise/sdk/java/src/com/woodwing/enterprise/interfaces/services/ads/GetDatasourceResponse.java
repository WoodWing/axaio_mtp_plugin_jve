/**
 * GetDatasourceResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class GetDatasourceResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.ads.Query[] queries;

    private com.woodwing.enterprise.interfaces.services.ads.Setting[] settings;

    private com.woodwing.enterprise.interfaces.services.ads.Publication[] publications;

    public GetDatasourceResponse() {
    }

    public GetDatasourceResponse(
           com.woodwing.enterprise.interfaces.services.ads.Query[] queries,
           com.woodwing.enterprise.interfaces.services.ads.Setting[] settings,
           com.woodwing.enterprise.interfaces.services.ads.Publication[] publications) {
           this.queries = queries;
           this.settings = settings;
           this.publications = publications;
    }


    /**
     * Gets the queries value for this GetDatasourceResponse.
     * 
     * @return queries
     */
    public com.woodwing.enterprise.interfaces.services.ads.Query[] getQueries() {
        return queries;
    }


    /**
     * Sets the queries value for this GetDatasourceResponse.
     * 
     * @param queries
     */
    public void setQueries(com.woodwing.enterprise.interfaces.services.ads.Query[] queries) {
        this.queries = queries;
    }


    /**
     * Gets the settings value for this GetDatasourceResponse.
     * 
     * @return settings
     */
    public com.woodwing.enterprise.interfaces.services.ads.Setting[] getSettings() {
        return settings;
    }


    /**
     * Sets the settings value for this GetDatasourceResponse.
     * 
     * @param settings
     */
    public void setSettings(com.woodwing.enterprise.interfaces.services.ads.Setting[] settings) {
        this.settings = settings;
    }


    /**
     * Gets the publications value for this GetDatasourceResponse.
     * 
     * @return publications
     */
    public com.woodwing.enterprise.interfaces.services.ads.Publication[] getPublications() {
        return publications;
    }


    /**
     * Sets the publications value for this GetDatasourceResponse.
     * 
     * @param publications
     */
    public void setPublications(com.woodwing.enterprise.interfaces.services.ads.Publication[] publications) {
        this.publications = publications;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDatasourceResponse)) return false;
        GetDatasourceResponse other = (GetDatasourceResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.queries==null && other.getQueries()==null) || 
             (this.queries!=null &&
              java.util.Arrays.equals(this.queries, other.getQueries()))) &&
            ((this.settings==null && other.getSettings()==null) || 
             (this.settings!=null &&
              java.util.Arrays.equals(this.settings, other.getSettings()))) &&
            ((this.publications==null && other.getPublications()==null) || 
             (this.publications!=null &&
              java.util.Arrays.equals(this.publications, other.getPublications())));
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
        if (getQueries() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getQueries());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getQueries(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSettings() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSettings());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSettings(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPublications() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPublications());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPublications(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetDatasourceResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("queries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Queries"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "Query"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Query"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("settings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Settings"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "Setting"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Setting"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publications");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publications"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", "Publication"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Publication"));
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
